<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Checkout</h1>
    <p>Subtotal: $<?= number_format($subtotal / 100, 2) ?></p>
    <form method="post">
        <?= csrf_field() ?>
        <p><label>Email<br><input type="email" name="email" value="<?= auth()->loggedIn() ? esc(auth()->user()->email) : '' ?>" required></label></p>
        <p><label>Shipping Address<br><textarea name="shipping_address" rows="3" required></textarea></label></p>
        <p><label>Discount Code<br><input type="text" name="discount_code"></label></p>
        <button class="pj-btn pj-btn-gold" type="submit">Pay Now</button>
    </form>
</div>
<?= $this->endSection() ?>
