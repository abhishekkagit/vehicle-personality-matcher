# Vehicle Personality Matcher

Vehicle Personality Matcher is a PHP and MySQL web application that helps users find cars and bikes that match their lifestyle, preferences, and driving personality. Instead of only browsing vehicles by specs, users can take a compatibility quiz and receive a personalized match score, regret prediction, comparison insights, and AI-generated feedback.

## Features

- Browse cars and bikes with detailed specifications
- Take a lifestyle-based vehicle compatibility quiz
- Get personalized match scores and regret prediction
- Compare cars and bikes side by side
- Save favorite vehicles
- View quiz history and saved comparisons
- User registration, login, profile, and dashboard
- Google OAuth login support
- Admin dashboard for managing vehicles, users, feedback, and settings
- AI-powered vehicle feedback using Google Gemini
- Groq fallback support when Gemini is unavailable
- Vehicle assistant chat for asking questions about a selected vehicle
- Dynamic UI with animations, charts, and interactive effects

## Tech Stack

### Backend
- PHP
- MySQL
- MySQLi
- Composer
- cURL

### Frontend
- HTML5
- CSS3
- JavaScript
- Chart.js
- AOS Animation Library
- GSAP
- Typed.js
- Particles.js

### Authentication
- Custom email/password authentication
- Google OAuth using `google/apiclient`

### AI Integration
- Google Gemini API
- Groq API fallback

### Development Environment
- XAMPP
- Apache
- MySQL / phpMyAdmin

## Project Structure

```text
vehicle-personality-matcher/
├── admin/              # Admin dashboard and management pages
├── api/                # API endpoints
├── assets/             # CSS, JavaScript, and images
├── auth/               # Authentication and Google OAuth
├── config/             # API keys and configuration files
├── includes/           # Database connection and reusable logic
├── scripts/            # Utility scripts
├── user/               # User dashboard and account pages
├── uploads/            # Uploaded user files
├── vendor/             # Composer dependencies
├── index.php           # Home page
├── vehicles.php        # Vehicle listing page
├── vehicle-details.php # Vehicle details page
├── quiz.php            # Compatibility quiz
├── result.php          # Quiz result and AI feedback
├── compare.php         # Car comparison
├── compare_bikes.php   # Bike comparison
└── vehicle-chat.php    # AI vehicle assistant
