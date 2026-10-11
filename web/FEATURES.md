# Prayer Journal — 50 Features

Fifty capabilities across the website, the Studio admin panel and the mobile app. Each one has been implemented and checked in this codebase. Most can be configured in **Studio**, and nothing needs code changes to edit.

## Learning platform

1. **Modular courses.** Courses are split into modules, and each module holds lessons, quizzes and assignments in any order.
2. **Drag-and-drop curriculum builder.** Reorder modules and steps with the mouse, or with Alt + arrow keys. Every change saves instantly.
3. **Six lesson types.** Reading, devotion, video (YouTube/Vimeo), audio, document and interactive embed.
4. **Drip scheduling.** Release a lesson a set number of days after enrolment, or on a fixed date.
5. **Sequential mode.** Learners unlock each step by finishing the previous one.
6. **Free previews.** Mark any lesson as a public preview to let visitors sample a course.
7. **Journal prompts in every lesson.** "Prayer points", "meditation" and "gratitude" boxes autosave into the learner's Prayer Journal. Defaults are set per course and can be overridden per lesson.
8. **Key-scripture panel.** Each lesson shows its Bible passage in full beside the teaching.
9. **Quizzes.** Four question types: single choice, multiple choice, true/false and short answer (several answers accepted). Each quiz can have a pass mark, time limit, attempt limit, shuffling and per-question explanations.
10. **Assignments and grading.** Learners submit text and files. Instructors grade from a queue with feedback, and a per-course gradebook collects the results.
11. **Automatic certificates.** A certificate is issued when the course completion rule is met. Title, signatory and message are configurable, and the certificate can be printed with the logo.
12. **Course settings.** Completion rule, enrolment cap, time-limited access, discussion/review/journal switches, course colours, and welcome and completion messages.
13. **Three access models.** Free, one-time purchase, or included in a membership plan.
14. **Learner engagement tools.** Lesson discussions, private notes, course announcements (with notifications), and star reviews.
15. **Instructor analytics.** Enrolment trends, where learners are in the course, per-learner progress and a student roster for each course.
16. **The "My Prayer Journal" course, built from the book.** It has 8 modules, 131 lessons, 121 devotions, 6 review quizzes and 1 assignment. It is fully editable, and `php spark import:prayer-journal` rebuilds it.

## Prayer Journal

17. **A private journal with four entry kinds.** Prayer, meditation, gratitude and notes, with search, filters and statistics.
18. **Answered-prayer tracking.** Mark a prayer answered, add a testimony, and filter "waiting" vs "answered".
19. **Printable journal.** A clean, logo-branded print layout of the learner's entries.

## Bible

20. **A clean full-KJV reader.** The source's editorial braces are removed. Readers choose a serif or sans font and the text size, and keyboard shortcuts move between chapters.
21. **Highlights, bookmarks and notes.** Five highlight colours, collected in a personal library page.
22. **Powerful search.** Search words, "exact phrases" or a single testament, with matches highlighted. Typing a reference such as "Romans 8:28" jumps straight to it.
23. **Reading plans.** Five built-in plans with day-by-day progress tracking.
24. **Reading streaks.** A daily streak encourages consistency.
25. **Verse of the day.** It appears on the home page, the dashboard and the mobile app, and can be shared.
26. **Scripture everywhere.** Bible references in lessons, journals and posts become links automatically. Hovering one shows the verse in a popover.

## Community

27. **Prayer wall.** Requests can be posted by category or anonymously. An "I prayed" button keeps a live count and notifies the author, and answered requests show testimonies.
28. **Notifications centre.** Covers prayers, grades, announcements and shipped orders.
29. **Universal search.** One search box covers courses, lessons, scripture, products and (privately) the user's own journal.
30. **Personal dashboard.** Greeting, next lesson, weekly activity, streak, journal stats, prayers still waiting, and today's reading.

## Shop (Shopify / WooCommerce-class)

31. **Product variants.** Up to three options (for example cover × size). The variant generator also supports compare-at (sale) prices.
32. **Physical and digital products.** Secure downloads are served only to paying customers.
33. **Inventory management.** Stock per variant, adjustments with reasons, a movement history, backorders and low-stock badges.
34. **Collections and categories.** Featured collections with banners, plus nested categories.
35. **Discount codes.** Percentage, fixed amount or free shipping, with minimum order, usage limit, start/end dates and pause/resume.
36. **Shipping zones and rates.** Zones by country, with a "rest of world" fallback, rate minimums and a free-shipping threshold.
37. **Tax rules.** VAT/GST per country or region, with prices shown tax-inclusive or tax-exclusive.
38. **Checkout.** Guest checkout, PayFast and Stripe with amount verification, and private order pages.
39. **Order management.** A timeline of events, internal notes, fulfilment with carrier and tracking (the customer is notified), and status changes. Cancelling or refunding returns stock automatically.
40. **Reviews and wishlists.** Product ratings, and a personal wishlist.
41. **Sales reports.** Revenue chart, average order value, top products, discount usage and new sign-ups over 7, 30, 90 or 365 days, with CSV export.
42. **Membership plans.** Monthly, yearly or lifetime plans that unlock every membership course.

## Studio admin and platform

43. **One admin panel, role-aware.** Instructors see learning tools, vendors see the shop, admins see everything. Roles are managed in-app, and only a superadmin can grant superadmin.
44. **No-code branding.** Upload a logo and set brand colours, home page copy, the announcement bar, footer and store settings.
45. **Professional design system.** Light, dark and auto themes. Per-user text size and reading font are remembered, and the layout is responsive from phone to desktop.
46. **Consistent brand.** Clean SVG line icons are used throughout (no emoji). The logo appears on every page: website, Studio, sign-in, certificates, print views and error pages.
47. **Rich-text editor with a safe sanitiser.** Headings, lists, quotes, links and video embeds, with dangerous markup removed. Only YouTube and Vimeo embeds are allowed.

## Mobile app and engineering

48. **Native mobile app (Expo).** Logo on every screen and line icons, with dark mode and reading size. It includes the dashboard, course player with autosaving journal prompts, quizzes, the Bible with highlight/bookmark/share, the journal, the prayer wall, and shop with cart.
49. **REST API.** `/api/v1` with token authentication powers the app. Quiz answers never reach the device, and a CORS profile allows browser builds.
50. **Security and quality.** CSRF on every form, signed webhooks, ownership checks, upload validation, and test payments locked in production. An automated suite of 38 tests covers security, the API, the LMS, the shop and the course import.
