export type User = { id: number; username: string; email: string; groups: string[] };

export type Verse = { reference: string; text: string; book: string; chapter: number; verse: number };

export type AppConfig = {
  name: string;
  tagline: string;
  logo_url: string;
  currency: string;
  announcement: string;
  web_url: string;
  verse_of_the_day: Verse | null;
  prayer_categories: Record<string, string>;
  journal_kinds: Record<string, string>;
};

export type MyCourse = {
  course_id: number;
  slug: string;
  title: string;
  subtitle: string | null;
  thumbnail_url: string | null;
  progress: number;
  status: string;
  last_lesson_id: number | null;
  last_lesson_title: string | null;
};

export type JournalStats = { total: number; prayers: number; answered: number; gratitude: number; meditations: number };

export type Dashboard = {
  user: { id: number; username: string; name: string };
  verse_of_the_day: Verse | null;
  courses: MyCourse[];
  journal: JournalStats;
  streak: number;
  plans: { id: number; title: string; percent: number; next_day: number; today: { day: number; label: string; readings: string } | null }[];
  unread_notifications: number;
};

export type Course = {
  id: number;
  slug: string;
  title: string;
  subtitle: string | null;
  summary: string | null;
  thumbnail_url: string | null;
  level: string | null;
  category: string | null;
  price_cents: number;
  currency: string;
  access_mode: string;
  lesson_count: number;
  total_minutes: number;
  student_count: number;
  rating: number | null;
};

export type CurriculumItem = {
  kind: 'lesson' | 'quiz' | 'assignment';
  id: number;
  title: string;
  type: string;
  duration: number;
  is_preview: boolean;
  done: boolean;
  locked: boolean;
  reason: string | null;
};

export type Section = { id: number | null; title: string; summary: string | null; done_count: number; items: CurriculumItem[] };

export type CourseDetail = Course & {
  description: string;
  what_you_learn: string[];
  requirements: string[];
  author_name: string | null;
  author_bio: string | null;
  enrolled: boolean;
  progress: number;
  sections: Section[];
};

export type Prompt = { key: string; label: string; hint: string; kind: string; answer: string };

export type Lesson = {
  id: number;
  title: string;
  type: string;
  summary: string | null;
  content_html: string;
  video_url: string | null;
  duration_mins: number;
  bible_reference: string | null;
  scripture: { label: string; url: string; verses: { verse: number; text: string }[] } | null;
  done: boolean;
  section_title: string | null;
  prev: { kind: string; id: number; title: string } | null;
  next: { kind: string; id: number; title: string; locked: boolean } | null;
  prompts: Prompt[];
};

export type Quiz = {
  id: number;
  title: string;
  description: string | null;
  pass_percent: number;
  time_limit_mins: number;
  attempts_left: number | null;
  best: { score_percent: number; passed: number } | null;
  questions: { id: number; type: 'single' | 'multiple' | 'true_false' | 'short_text'; prompt: string; points: number; options: { id: number; text: string }[] }[];
};

export type QuizResult = {
  score_percent: number;
  passed: number | boolean;
  review: Record<string, { correct: boolean; correct_ids?: number[]; explanation?: string | null }>;
  progress: number;
};

export type JournalEntry = {
  id: number;
  kind: 'prayer' | 'meditation' | 'gratitude' | 'note';
  title: string | null;
  body: string;
  scripture: string | null;
  is_answered: boolean;
  answered_at: string | null;
  answer_note: string | null;
  entry_date: string;
  course_title: string | null;
  lesson_title: string | null;
  lesson_id: number | null;
  created_at: string;
};

export type BibleBook = { id: number; name: string; abbrev: string; testament: 'OT' | 'NT'; position: number; chapter_count: number };

export type BibleVerse = { id: number; verse: number; text: string; chapter?: number; book_name?: string; book_position?: number };

export type Chapter = {
  book: BibleBook & { slug: string };
  chapter: number;
  has_prev: boolean;
  has_next: boolean;
  verses: BibleVerse[];
  highlights: Record<string, string>;
  bookmarks: number[];
};

export type PrayerRequest = {
  id: number;
  body: string;
  category: string;
  author: string;
  is_mine: boolean;
  prayer_count: number;
  i_prayed: boolean;
  is_answered: boolean;
  answer_note: string | null;
  created_at: string;
};

export type ProductSummary = {
  id: number;
  slug: string;
  title: string;
  short_description: string | null;
  product_type: string;
  image_url: string | null;
  price_cents: number;
  compare_at_cents: number | null;
  rating: number | null;
  is_featured: boolean;
  in_stock: boolean;
};

export type Product = {
  id: number;
  slug: string;
  title: string;
  short_description: string | null;
  description_html: string;
  product_type: string;
  brand: string | null;
  images: { url: string; alt: string }[];
  options: { name: string; values: string[] }[];
  variants: { id: number; sku: string; label: string; options: string[]; price_cents: number; compare_at_cents: number | null; in_stock: boolean }[];
  reviews: { rating: number; title: string | null; body: string; author: string; created_at: string }[];
  wishlisted: boolean;
  web_url: string;
};

export type Cart = {
  items: { id: number; variant_id: number; title: string; slug: string; variant: string; quantity: number; unit_price_cents: number; image_url: string | null }[];
  count: number;
  subtotal_cents: number;
  currency: string;
  checkout_url: string;
};
