# FloraFinder - Plant Identification & Community Platform

FloraFinder is a Laravel-based web application that helps users identify Malaysian plants using AI technology. It
features plant identification via image upload, a community forum, conservation tracking, and educational resources.

## Features

- **Plant Identification**: Upload images of plants to identify them using the PlantNet API
- **Plant Database**: Access information about Malaysian native and endemic plant species
- **Plant Map**: Interactive map displaying plant locations and distributions
- **Plant Care Details**: Detailed care instructions for identified plants
- **Community Forum**: Discuss plant-related topics with other enthusiasts
- **User Authentication**: Secure registration and login system
- **Responsive Design**: Works on desktop and mobile devices

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Vue.js 3 with Inertia.js
- **UI Framework**: Tailwind CSS with custom components
- **API Integration**: Saloon PHP HTTP client library
- **Database**: SQLite (default), supports MySQL/PostgreSQL
- **Authentication**: Laravel's built-in authentication
- **Queue System**: Database queue for background jobs
- **Testing**: PHPUnit with Pest

## Installation

1. Clone the repository:

```bash
git clone https://github.com/anasdaniel/FloraFinder.git
cd FloraFinder
```

2. Install PHP dependencies:

```bash
composer install
```

3. Install JavaScript dependencies:

```bash
npm install
```

4. Create your environment file:

```bash
cp .env.example .env
```

5. Generate an application key:

```bash
php artisan key:generate
```

6. Configure your database in the `.env` file:

```
DB_CONNECTION=sqlite
# Or use MySQL/PostgreSQL by configuring the appropriate settings:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
```

7. Set up your PlantNet API key in the `.env` file:

```
PLANTNET_API_KEY=your_plantnet_api_key_here
PLANTNET_PROJECT=all
```

8. Set up your Trefle API key in the `.env` file:

```
TREFLE_API_KEY=your_trefle_api_key_here
```

9. Set up your IUCN API key in the `.env` file:

```
IUCN_API_KEY=your_iucn_api_key_here
```

10. Run database migrations:

```bash
php artisan migrate
```

11. Build frontend assets:

```bash
npm run build
```

12. Run the development server:

```bash
composer run dev
```
