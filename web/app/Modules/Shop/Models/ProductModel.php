<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'vendor_id', 'category_id', 'title', 'slug', 'description', 'status', 'short_description', 'product_type',
        'brand', 'tags', 'options', 'seo_title', 'seo_description', 'is_featured', 'track_inventory', 'allow_backorder',
        'published_at', 'allow_download', 'flipbook',
    ];
    protected $useTimestamps = true;

    public const SORTS = [
        'featured'   => 'Featured',
        'newest'     => 'Newest',
        'best'       => 'Best selling',
        'price_asc'  => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'rating'     => 'Top rated',
        'title'      => 'A–Z',
    ];

    public function published()
    {
        return $this->where('products.status', 'published');
    }

    /**
     * Product listing with computed price range, sale price, stock, images
     * and rating. Supports filters used by the storefront and admin.
     *
     * @return list<array>
     */
    public function storefront(array $f = []): array
    {
        return $this->storefrontQuery($f)->get()->getResultArray();
    }

    /**
     * @return array{items:list<array>, total:int, page:int, pages:int, per_page:int}
     */
    public function storefrontPage(array $f, int $page = 1, int $perPage = 12): array
    {
        $count = $this->storefrontQuery(array_merge($f, ['limit' => null, 'offset' => null, 'sort' => 'none']))->countAllResults();
        $page  = max(1, $page);
        $items = $this->storefrontQuery(array_merge($f, ['limit' => $perPage, 'offset' => ($page - 1) * $perPage]))->get()->getResultArray();

        return ['items' => $items, 'total' => $count, 'page' => $page, 'pages' => max(1, (int) ceil($count / $perPage)), 'per_page' => $perPage];
    }

    private function storefrontQuery(array $f)
    {
        $db    = $this->db;
        $inner = $db->table('products p')
            ->select('p.*, pc.name AS category_name, pc.slug AS category_slug')
            ->select('(SELECT MIN(v.price_cents) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS price_cents', false)
            ->select('(SELECT MAX(v.price_cents) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS max_price_cents', false)
            ->select('(SELECT v.compare_at_cents FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 ORDER BY v.price_cents ASC, v.position ASC LIMIT 1) AS compare_at_cents', false)
            ->select('(SELECT v.id FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 ORDER BY v.position ASC, v.id ASC LIMIT 1) AS default_variant_id', false)
            ->select('(SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS variant_count', false)
            ->select('(SELECT COALESCE(SUM(il.quantity), 0) FROM inventory_levels il JOIN product_variants v ON v.id = il.variant_id WHERE v.product_id = p.id AND v.is_active = 1) AS stock', false)
            ->select('(SELECT i.path FROM product_images i WHERE i.product_id = p.id ORDER BY i.position ASC, i.id ASC LIMIT 1) AS image', false)
            ->select('(SELECT i.path FROM product_images i WHERE i.product_id = p.id ORDER BY i.position ASC, i.id ASC LIMIT 1 OFFSET 1) AS image2', false)
            ->select("(SELECT AVG(r.rating) FROM product_reviews r WHERE r.product_id = p.id AND r.status = 'published') AS avg_rating", false)
            ->select("(SELECT COUNT(*) FROM product_reviews r WHERE r.product_id = p.id AND r.status = 'published') AS review_count", false)
            ->select('(SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN product_variants v ON v.id = oi.variant_id WHERE v.product_id = p.id) AS units_sold', false)
            ->join('product_categories pc', 'pc.id = p.category_id', 'left')
            ->where('p.deleted_at IS NULL');

        if (empty($f['all_statuses'])) {
            $inner->where('p.status', 'published');
        } elseif (! empty($f['status'])) {
            $inner->where('p.status', $f['status']);
        }
        if (! empty($f['vendor_id'])) {
            $inner->where('p.vendor_id', (int) $f['vendor_id']);
        }
        if (! empty($f['q'])) {
            $inner->groupStart()->like('p.title', $f['q'])->orLike('p.tags', $f['q'])->orLike('p.brand', $f['q'])->orLike('p.short_description', $f['q'])->groupEnd();
        }
        if (! empty($f['category'])) {
            $cat = $db->table('product_categories')->where('slug', $f['category'])->get()->getRowArray();
            $ids = $cat ? array_merge([(int) $cat['id']], array_map('intval', array_column($db->table('product_categories')->select('id')->where('parent_id', $cat['id'])->get()->getResultArray(), 'id'))) : [0];
            $inner->whereIn('p.category_id', $ids);
        }
        if (! empty($f['collection'])) {
            $inner->where('p.id IN (SELECT cp.product_id FROM collection_products cp JOIN collections c ON c.id = cp.collection_id WHERE c.slug = ' . $db->escape($f['collection']) . ')', null, false);
        }
        if (! empty($f['type']) && in_array($f['type'], ['physical', 'digital'], true)) {
            $inner->where('p.product_type', $f['type']);
        }
        if (! empty($f['tag'])) {
            $inner->like('p.tags', $f['tag']);
        }
        if (! empty($f['exclude'])) {
            $inner->where('p.id !=', (int) $f['exclude']);
        }
        if (! empty($f['ids'])) {
            $inner->whereIn('p.id', array_map('intval', (array) $f['ids']));
        }

        $outer = $db->newQuery()->fromSubquery($inner, 't')->select('t.*');
        if (isset($f['min']) && $f['min'] !== '' && $f['min'] !== null) {
            $outer->where('t.price_cents >=', (int) round((float) $f['min'] * 100));
        }
        if (isset($f['max']) && $f['max'] !== '' && $f['max'] !== null) {
            $outer->where('t.price_cents <=', (int) round((float) $f['max'] * 100));
        }
        if (! empty($f['in_stock'])) {
            $outer->groupStart()->where('t.stock >', 0)->orWhere('t.product_type', 'digital')->orWhere('t.track_inventory', 0)->orWhere('t.allow_backorder', 1)->groupEnd();
        }
        if (! empty($f['on_sale'])) {
            $outer->where('t.compare_at_cents > t.price_cents', null, false);
        }

        switch ($f['sort'] ?? 'featured') {
            case 'none':
                break;
            case 'newest':
                $outer->orderBy('t.created_at', 'DESC');
                break;
            case 'best':
                $outer->orderBy('t.units_sold', 'DESC');
                break;
            case 'price_asc':
                $outer->orderBy('t.price_cents', 'ASC');
                break;
            case 'price_desc':
                $outer->orderBy('t.price_cents', 'DESC');
                break;
            case 'rating':
                $outer->orderBy('t.avg_rating', 'DESC');
                break;
            case 'title':
                $outer->orderBy('t.title', 'ASC');
                break;
            case 'updated':
                $outer->orderBy('t.updated_at', 'DESC');
                break;
            default:
                $outer->orderBy('t.is_featured', 'DESC')->orderBy('t.units_sold', 'DESC')->orderBy('t.created_at', 'DESC');
        }
        if (! empty($f['limit'])) {
            $outer->limit((int) $f['limit'], (int) ($f['offset'] ?? 0));
        }

        return $outer;
    }

    public function withVariantsAndImages(int $id): ?array
    {
        $product = $this->find($id);
        if (! $product) {
            return null;
        }
        $variants = $this->db->table('product_variants')->where('product_id', $id)->orderBy('position')->orderBy('id')->get()->getResultArray();
        $stock    = [];
        if ($variants !== []) {
            foreach ($this->db->table('inventory_levels')->select('variant_id, SUM(quantity) AS qty')->whereIn('variant_id', array_column($variants, 'id'))->groupBy('variant_id')->get()->getResultArray() as $r) {
                $stock[(int) $r['variant_id']] = (int) $r['qty'];
            }
        }
        foreach ($variants as &$v) {
            $v['stock'] = $stock[(int) $v['id']] ?? 0;
        }
        $product['variants']    = $variants;
        $product['images']      = $this->db->table('product_images')->where('product_id', $id)->orderBy('position', 'asc')->orderBy('id', 'asc')->get()->getResultArray();
        $product['option_defs'] = self::options($product);

        return $product;
    }

    /**
     * @return list<array{name:string, values:list<string>}>
     */
    public static function options(array $product): array
    {
        $opts = json_decode((string) ($product['options'] ?? ''), true);
        if (! is_array($opts)) {
            return [];
        }
        $out = [];
        foreach (array_slice($opts, 0, 3) as $o) {
            $name   = trim((string) ($o['name'] ?? ''));
            $values = array_values(array_unique(array_filter(array_map('trim', (array) ($o['values'] ?? [])), 'strlen')));
            if ($name !== '' && $values !== []) {
                $out[] = ['name' => $name, 'values' => $values];
            }
        }

        return $out;
    }

    public static function isPurchasable(array $product, array $variant): bool
    {
        if (empty($variant['is_active'])) {
            return false;
        }
        if (($product['product_type'] ?? 'physical') === 'digital' || ! (int) ($product['track_inventory'] ?? 1) || (int) ($product['allow_backorder'] ?? 0)) {
            return true;
        }

        return (int) ($variant['stock'] ?? 0) > 0;
    }

    public function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = url_title($title, '-', true) ?: 'product';
        $slug = $base;
        $i    = 2;
        while (true) {
            $b = $this->withDeleted()->where('slug', $slug);
            if ($ignoreId) {
                $b->where('id !=', $ignoreId);
            }
            if (! $b->first()) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }

    public function reviews(int $productId): array
    {
        return $this->db->table('product_reviews r')
            ->select('r.*, u.username')
            ->join('users u', 'u.id = r.user_id')
            ->where('r.product_id', $productId)->where('r.status', 'published')
            ->orderBy('r.created_at', 'DESC')
            ->get()->getResultArray();
    }

    public function categories(): array
    {
        return $this->db->table('product_categories pc')
            ->select("pc.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = pc.id AND p.status = 'published' AND p.deleted_at IS NULL) AS product_count", false)
            ->orderBy('pc.position')->orderBy('pc.name')
            ->get()->getResultArray();
    }

    public function collections(bool $featuredOnly = false): array
    {
        $b = $this->db->table('collections c')
            ->select('c.*, (SELECT COUNT(*) FROM collection_products cp WHERE cp.collection_id = c.id) AS product_count', false);
        if ($featuredOnly) {
            $b->where('c.is_featured', 1);
        }

        return $b->orderBy('c.position')->orderBy('c.title')->get()->getResultArray();
    }

    public function collectionIds(int $productId): array
    {
        return array_map('intval', array_column($this->db->table('collection_products')->select('collection_id')->where('product_id', $productId)->get()->getResultArray(), 'collection_id'));
    }
}
