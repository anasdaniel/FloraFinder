# 🌿 FloraFinder - Malaysian Plant Identification & Community Platform

[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue.js-3-green.svg)](https://vuejs.org)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

FloraFinder is a sophisticated web application dedicated to the discovery, identification, and conservation of Malaysian flora. Built with Laravel 12 and Vue 3, it provides a high-performance platform for plant enthusiasts to identify species, track sightings, and engage with a community of experts.

## ✨ Key Features

- **🤖 AI-Powered Identification**: Instant identification of Malaysian plants using the PlantNet API integration.
- **🔔 Dynamic Seasonal Alerts**: A hybrid alert system that combines:
    - **Live iNaturalist Sync**: Real-time phenology data (flowering/fruiting) for key species like Durian, Rambutan, and Mangosteen.
    - **Botanical Calendar**: Static alerts based on historical blooming and fruiting seasons in Malaysia.
- **🗺️ Interactive Discovery Map**: Visualize plant distributions and user sightings using Leaflet.js with custom marker clustering and conservation status color-coding.
- **💬 Compact Community Forum**: A high-density, optimized forum interface for discussions, plant identification requests, and knowledge sharing.
- **📊 Personal Dashboard**: Comprehensive overview of your botanical journey, including activity charts, recent sightings, and conservation impact tracking.
- **🛡️ Conservation Focus**: Integration with IUCN Red List categories to highlight endangered and endemic Malaysian species.
- **📱 Modern Responsive UI**: Built with Tailwind CSS and Shadcn/UI, providing a seamless experience across desktop and mobile devices.

## 🛠️ Tech Stack

### Backend
- **Framework**: [Laravel 12.x](https://laravel.com)
- **PHP Version**: 8.2+
- **API Client**: [Saloon PHP](https://docs.saloon.dev/) for robust external API integrations (iNaturalist, PlantNet).
- **Database**: SQLite (Development), MySQL/PostgreSQL (Production).
- **Queue System**: Database-backed queues for background API syncing and image processing.

### Frontend
- **Framework**: [Vue.js 3.x](https://vuejs.org) (Composition API).
- **Bridge**: [Inertia.js](https://inertiajs.com) for a seamless SPA experience with server-side routing.
- **Styling**: [Tailwind CSS](https://tailwindcss.com) with [Shadcn/UI](https://shadcn-vue.com/) components.
- **Icons**: [Lucide Vue Next](https://lucide.dev/) for consistent, high-quality iconography.
- **Maps**: [Leaflet.js](https://leafletjs.com/) for interactive geospatial visualizations.

### External Integrations
- **iNaturalist API**: Real-time observation data for seasonal phenology.
- **PlantNet API**: Visual recognition for plant identification.
- **IUCN Red List**: Conservation status and threat assessment data.

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ & NPM
- SQLite (or your preferred database)
- Redis (for caching and queues)

### Quick Start

1. **Clone & Install**
   ```bash
   git clone https://github.com/anasdaniel/FloraFinder.git
   cd FloraFinder
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **API Keys Setup**
   Add your API keys to the `.env` file:
   ```env
   PLANTNET_API_KEY=your_key
   IUCN_API_KEY=your_key
   GEMINI_API_KEY=your_key
   ```

4. **Database Setup**
   ```bash
   # For SQLite
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. **Sync Seasonal Data**
   To populate the live seasonal alerts from iNaturalist:
   ```bash
   php artisan alerts:sync-inaturalist
   ```

6. **Run Development Server**
   ```bash
   # Using the built-in concurrent command
   composer dev
   ```

## 📂 Project Structure

- `app/Http/Integrations`: Saloon PHP connectors and requests for external APIs.
- `app/Services`: Core business logic (Seasonal Alerts, Plant Care, Caching).
- `app/Console/Commands`: Scheduled tasks for data synchronization.
- `resources/js/Pages`: Vue components for the Inertia-powered frontend.
- `resources/js/components/ui`: Reusable Shadcn/UI components.

## 🧪 Testing

Run the comprehensive test suite using Pest:
```bash
php artisan test
```

## 🤝 Contributing

We welcome contributions! Please see our contribution guidelines for more details.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---
Developed with ❤️ for the Malaysian Botanical Community.
