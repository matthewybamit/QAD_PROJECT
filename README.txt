
A simple PHP web application demonstrating basic routing, MVC structure, and Tailwind CSS integration.

Features:
- Custom PHP router for clean URL handling and 404 error pages.
- MVC-inspired organization with separate controllers and views.
- Reusable partials for head and navigation sections.
- Responsive navigation bar styled with a left-to-right blue gradient using Tailwind CSS.
- Landing page setup as the default route.

Getting Started:
1. Clone or download this repository.
2. Ensure you have PHP installed on your system.
3. (Optional) Set up a database connection in `website/config/db.php` if your project requires database access.
4. Start a local PHP server in the `website` directory:
   php -S localhost:8000
5. Visit http://localhost:8000 in your browser.

File Structure:
- website/
  - router.php           # Handles routing logic
  - controller/          # Controller files (e.g., landing.php, listing.php)
  - views/
    - landing.view.php   # Landing page view
    - partials/
      - head.php         # Head section partial
      - nav.php          # Navigation bar partial

Customization:
- Edit `router.php` to add or modify routes.
- Update Tailwind classes in `views/partials/nav.php` for custom navigation styles.
- Add new controllers and views as needed.

License:
This project is for educational purposes.