# Translator

A real-time translation chat interface powered by Claude AI.

## Features

- Session-based chat system with unique session tokens
- Real-time translation between multiple languages
- Support for Serbian, Albanian, Ukrainian, and many other languages
- German as default base language
- Share conversations via URL or QR code
- Voice input support (browser compatible)
- Responsive design for both desktop and mobile devices
- Simple, intuitive user interface
- Conversation summary generation
- Session expiry management (31 days)

## Development Setup

### Prerequisites

- Node.js 14+ and npm
- Claude API key

### Installation

1. Clone the repository
2. Install dependencies:
   ```
   npm install
   ```
3. Start the development server with hot reloading:
   ```
   npm run dev
   ```
   Or start without hot reloading:
   ```
   npm start
   ```
4. Open http://localhost:3000 in your browser

The development server uses nodemon for hot reloading, watching for changes in:
- server.js
- public/ directory
- Any .js, .html, or .css files

## LAMP Server Deployment

To deploy this application on a LAMP server (Linux, Apache, MySQL, PHP):

1. Make the deployment script executable:
   ```
   chmod +x deploy.sh
   ```

2. Run the deployment script:
   ```
   ./deploy.sh
   ```

3. Copy the contents of the `dist` directory to your web server's document root or virtual host directory

4. Configure your Apache server:
   - Enable mod_rewrite
   - Set AllowOverride to All for the directory in your Apache configuration

   Example Apache configuration:
   ```apache
   <Directory /var/www/html/translator>
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

5. Ensure PHP's cURL extension is enabled

6. Visit your website and test the application

## Configuration

- Set your Claude API key in the settings panel
- Choose your preferred languages for translation
- Toggle between customer and receiver modes
- Customize the customer name
- Edit the summary prompt for conversation summaries

## Sharing

In receiver mode, you can share the current session via:
1. A shareable URL that includes the session token and settings
2. A QR code that can be scanned by mobile devices
3. A downloadable PNG image of the QR code

The shared URL includes:
- Session token (for message history)
- API key (for translation functionality)
- Customer name
- Language settings

## Technologies Used

- Frontend: HTML, CSS, JavaScript
- Backend: Node.js/Express (Development) or PHP (LAMP Deployment)
- API: Claude AI for language translation

## License

MIT