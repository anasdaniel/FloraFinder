# 🌿 FloraFinder - Plant Identification & Community Platform

[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue.js-3-green.svg)](https://vuejs.org)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

FloraFinder is a comprehensive Laravel-based web application that helps users identify Malaysian plants using AI technology. It combines plant identification via image upload, an extensive plant database, interactive maps, community forums, and conservation tracking into a single platform for plant enthusiasts, researchers, and conservationists.

## ✨ Features

- **🤖 AI Plant Identification**: Upload images of plants to identify them using advanced PlantNet API integration
- **📚 Extensive Plant Database**: Access detailed information about Malaysian native and endemic plant species
- **🗺️ Interactive Maps**: Visualize plant locations, distributions, and user-contributed sightings on interactive maps
- **📍 Plant Sightings**: Community-driven platform for sharing and tracking plant sightings across Malaysia
- **🌱 Plant Care Details**: Get comprehensive care instructions and growing tips for identified plants
- **💬 Community Forum**: Engage with other plant enthusiasts through discussion threads and topics
- **👤 User Authentication**: Secure registration and login system with email verification
- **📱 Responsive Design**: Fully responsive interface that works seamlessly on desktop and mobile devices
- **🔍 Advanced Search**: Search through plant database with filters and advanced query options

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP Version**: 8.2+
- **Authentication**: Laravel Sanctum with email verification
- **API Integration**: Saloon PHP HTTP client library
- **Queue System**: Database queues for background processing
- **Caching**: Redis for high-performance caching
- **Database**: SQLite (development), MySQL/PostgreSQL (production)

### Frontend
- **Framework**: Vue.js 3.x with Composition API
- **SPA Framework**: Inertia.js for seamless page transitions
- **UI Framework**: Tailwind CSS with custom component library
- **Icons**: Lucide Vue for consistent iconography
- **Maps**: Leaflet.js with marker clustering
- **Build Tool**: Vite for fast development and optimized builds

### External APIs
- **PlantNet API**: AI-powered plant identification
- **Trefle API**: Comprehensive plant database
- **IUCN Red List API**: Conservation status information
- **Google Gemini AI**: Advanced plant descriptions and chat features

### Development & Testing
- **Testing**: PHPUnit with Pest testing framework
- **Code Quality**: Laravel Pint for code formatting
- **Linting**: ESLint for JavaScript/Vue code
- **Package Management**: Composer (PHP), npm (Node.js)

## 🚀 Installation

### Prerequisites

Before you begin, ensure you have the following installed on your system:
- **PHP 8.2 or higher**
- **Composer** (PHP dependency manager)
- **Node.js 18+ and npm** (for frontend assets)
- **SQLite** (default database) or **MySQL/PostgreSQL**
- **Redis** (for caching and queues)

### Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/anasdaniel/FloraFinder.git
   cd FloraFinder
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment Setup**
   ```bash
   # Copy environment file
   cp .env.example .env

   # Generate application key
   php artisan key:generate
   ```

5. **Database Configuration**

   **For SQLite (Recommended for development):**
   ```bash
   # SQLite will be created automatically
   touch database/database.sqlite
   ```

   **For MySQL/PostgreSQL:**
   Update your `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=florafinder
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Redis Configuration**
   ```env
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

7. **API Keys Setup**

   Get API keys from the respective services:

   - **PlantNet API**: [https://my-api.plantnet.org/](https://my-api.plantnet.org/)
   - **Trefle API**: [https://trefle.io/](https://trefle.io/)
   - **IUCN Red List API**: [https://apiv3.iucnredlist.org/](https://apiv3.iucnredlist.org/)
   - **Google Gemini API**: [https://makersuite.google.com/app/apikey](https://makersuite.google.com/app/apikey)

   Add them to your `.env` file:
   ```env
   PLANTNET_API_KEY=your_plantnet_api_key_here
   PLANTNET_PROJECT=all

   TREFLE_API_KEY=your_trefle_api_key_here

   IUCN_API_KEY=your_iucn_api_key_here

   GEMINI_API_KEY=your_gemini_api_key_here
   ```

8. **Database Migration**
   ```bash
   php artisan migrate
   ```

9. **Build Assets**
   ```bash
   npm run build
   ```

10. **Start Development Server**
    ```bash
    composer run dev
    ```

    This will start:
    - Laravel server on `http://localhost:8000`
    - Vite dev server for hot reloading
    - Queue worker for background jobs
    - Log monitoring

### Alternative Development Setup

If you prefer to run services separately:

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev

# Terminal 3: Queue worker
php artisan queue:work

# Terminal 4: Log monitoring (optional)
php artisan pail
```

## 🧪 Testing

Run the comprehensive test suite:

```bash
# Run all tests
php artisan test

# Run tests with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/Auth/AuthenticationTest.php

# Run tests in parallel for faster execution
php artisan test --parallel
```

### Test Structure
- **Feature Tests**: API endpoint testing and user workflows
- **Unit Tests**: Individual class and method testing
- **Browser Tests**: End-to-end user interface testing

## 🛠️ Development

### Available Commands

```bash
# Code formatting
composer run format        # Format PHP code with Pint
npm run format            # Format JS/Vue code with Prettier
npm run lint              # Lint JS/Vue code
npm run lint:fix          # Auto-fix linting issues

# Database operations
php artisan migrate        # Run migrations
php artisan migrate:fresh  # Reset database
php artisan db:seed        # Seed database with sample data

# Queue management
php artisan queue:work     # Process background jobs
php artisan queue:failed   # View failed jobs
php artisan queue:retry    # Retry failed jobs

# Cache management
php artisan cache:clear    # Clear application cache
php artisan config:clear   # Clear config cache
php artisan route:clear    # Clear route cache
```

### Code Quality

The project uses several tools to maintain code quality:

- **Laravel Pint**: PHP code formatting
- **ESLint**: JavaScript/Vue linting
- **Prettier**: Code formatting for JS/Vue files
- **PHPUnit**: Comprehensive test suite

### Project Structure

```
├── app/                    # Laravel application code
│   ├── Http/Controllers/   # HTTP controllers
│   ├── Models/            # Eloquent models
│   ├── Services/          # Business logic services
│   └── Policies/          # Authorization policies
├── resources/js/          # Vue.js frontend
│   ├── pages/             # Inertia.js pages
│   └── components/        # Reusable Vue components
├── routes/                # Route definitions
├── database/              # Migrations and seeders
└── tests/                 # Test files
```

## 🚀 Deployment

### Production Build

1. **Build frontend assets for production:**
   ```bash
   npm run build
   ```

2. **Optimize Laravel for production:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Set up production environment:**
   - Configure production database (MySQL/PostgreSQL recommended)
   - Set up Redis for caching and queues
   - Configure web server (Nginx/Apache)
   - Set up SSL certificate
   - Configure queue workers for background processing

### Environment Variables

For production, ensure these environment variables are set:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Redis
REDIS_HOST=your_redis_host
REDIS_PASSWORD=your_redis_password

# All API keys as configured in development
```

## 🤝 Contributing

We welcome contributions to FloraFinder! Here's how you can help:

### Getting Started

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Make your changes and add tests
4. Ensure all tests pass: `php artisan test`
5. Format your code: `composer run format && npm run format`
6. Commit your changes: `git commit -m "Add your feature"`
7. Push to your fork: `git push origin feature/your-feature-name`
8. Create a Pull Request

### Contribution Guidelines

- Follow PSR-12 coding standards for PHP
- Use Vue 3 Composition API patterns
- Write comprehensive tests for new features
- Update documentation for API changes
- Ensure responsive design for all new components
- Use meaningful commit messages

### Reporting Issues

When reporting bugs or requesting features:
- Use the issue templates provided
- Include steps to reproduce for bugs
- Provide browser/OS information
- Include screenshots for UI issues

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **PlantNet API** for AI-powered plant identification
- **Trefle** for comprehensive plant database
- **IUCN Red List** for conservation status data
- **Google Gemini** for AI-generated plant descriptions
- **Laravel & Vue.js communities** for excellent frameworks
