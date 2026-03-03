Ancestral Viability Bot (AVB) is a custom WordPress plugin developed to bridge the gap between complex data entry and intuitive user interfaces. It integrates backend PHP logic with an asynchronous, JavaScript-driven conversational flow to automate genealogical and legal data collection.

🚀 Project Purpose
The AVB was engineered to solve the challenge of complex data acquisition within the WordPress ecosystem. By replacing traditional, static forms with a conversational, flow-based interface, it ensures a frictionless user experience while capturing vital information directly into the server.

🛠️ Technology Stack
Backend: PHP 8.x, leveraging WordPress core hooks (add_action, add_filter).

Frontend: Modern JavaScript (ES6+), focused on DOM manipulation and event-driven architecture.

Communication: Asynchronous AJAX requests, providing a "Single Page" experience without browser reloads.

Environment: Developed locally using Local by Flywheel (Nginx/PHP/MySQL) and VS Code.

Architecture: Modular, independent plugin structure (decoupled from the theme).

🔑 Key Technical Features
Asynchronous Conversational Engine: Communicates directly with custom WordPress REST API endpoints to process bot responses in real-time.

Intelligent Data Capture: Implements server-side validation before persisting data, ensuring high-quality, sanitized data input.

Performance-First Design: Strategic use of wp_enqueue_script with footer-loading parameters to ensure the bot initialization does not impact Core Web Vitals.

Security & Best Practices: Full implementation of WordPress sanitization and escaping functions (sanitize_text_field, esc_sql) to mitigate XSS and SQL injection risks.

Workflow: Professional version control using Git Flow (Feature branches: develop / feature/*).

📂 Plugin Structure
🛠️ Installation
Download the repository as a ZIP file.

Navigate to your WordPress Dashboard: Plugins > Add New > Upload Plugin.

Upload the ZIP file and click Install Now.

Activate the plugin.

Deploy the bot anywhere on your site using the shortcode: [avb_bot]

👨‍💻 Developed by
Javiam Allen | Full Stack WordPress Developer