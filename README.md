# InnoTravel - Comprehensive Travel Management Platform

![InnoTravel Logo](public/frontoffice/images/InnoTravelLOGO.png)

## Overview

InnoTravel is a comprehensive travel management platform developed as part of the PIDEV (Projet d'Intégration Développement) course at **Esprit School of Engineering**. The project focuses on creating an immersive travel experience platform specifically tailored for Tunisia, combining modern web technologies with user-friendly interfaces to provide a seamless travel planning and management experience.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)
- [Acknowledgments](#acknowledgments)

---

## Features

### User Management
- **User Authentication**: Secure login and registration system
- **Profile Management**: Personalized user profiles with profile picture upload
- **Role-Based Access**: Different interfaces for travelers, organizers, and administrators

### Trip Planning & Itineraries
- **Trip Creation**: Create personalized trips with detailed itineraries
- **Itinerary Management**: Plan day-by-day activities for trips
- **Budget Planning**: Set and track travel budgets

### Accommodation
- **Hotel Listings**: Browse and search for hotels across Tunisia
- **Booking System**: Make and manage hotel reservations
- **Rating & Reviews**: View and submit hotel ratings and reviews

### Transportation
- **Vehicle Rental**: Browse and book various transportation options
- **Reservation Management**: Track and manage vehicle reservations
- **Vehicle Categories**: Filter vehicles by type, capacity, and features

### Events & Activities
- **Event Discovery**: Browse cultural events and activities in Tunisia
- **Event Booking**: Purchase tickets for events
- **Event Management**: For organizers to create and manage events

### Blog & Community
- **Travel Blog**: Read and share travel experiences
- **Comments & Reactions**: Engage with blog content through comments and reactions
- **Content Management**: Admin tools for managing blog content

### Administrative Features
- **User Management**: Admin tools for managing users
- **Statistics Dashboard**: Visualize platform usage and booking statistics
- **Content Moderation**: Tools for moderating user-generated content

---

## Tech Stack

### Backend
- **Framework**: Symfony 6.4
- **Language**: PHP 8.1+
- **Database**: MySQL
- **ORM**: Doctrine

### Frontend
- **Template Engine**: Twig
- **CSS Framework**: Bootstrap
- **JavaScript Libraries**:
  - Chart.js (for statistics visualization)
  - Swiper (for sliders and carousels)
  - Stimulus (for JavaScript behaviors)
  - Turbo (for page transitions)

### Additional Technologies
- **Payment Processing**: Stripe integration
- **Email Service**: Custom mailer service
- **File Upload**: VichUploaderBundle
- **PDF Generation**: DomPDF
- **QR Code Generation**: Endroid QR Code

---

## Project Structure

The project follows the standard Symfony directory structure with some customizations:

```
InnoTravel/
├── assets/                  # Frontend assets (JS, CSS)
├── bin/                     # Symfony console and other executables
├── config/                  # Configuration files
├── migrations/              # Database migrations
├── public/                  # Publicly accessible files
│   ├── backoffice/          # Admin interface assets
│   ├── frontoffice/         # User interface assets
│   └── uploads/             # User uploaded content
├── src/                     # Application source code
│   ├── Command/             # Custom CLI commands
│   ├── Controller/          # Request handlers
│   ├── Entity/              # Database entity classes
│   ├── Form/                # Form type classes
│   ├── Repository/          # Database query classes
│   └── Service/             # Business logic services
├── templates/               # Twig templates
│   ├── admin/               # Admin interface templates
│   ├── user/                # User interface templates
│   └── base*.html.twig      # Base templates
└── var/                     # Generated files (cache, logs)
```

---

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/InnoTravel.git
   cd InnoTravel
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install JavaScript dependencies:
   ```bash
   npm install
   ```

4. Configure the database in `.env`:
   ```
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/innotravel?serverVersion=8.0"
   ```

5. Create the database and run migrations:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. Load fixtures (optional):
   ```bash
   php bin/console doctrine:fixtures:load
   ```

7. Build assets:
   ```bash
   npm run build
   ```

8. Start the Symfony development server:
   ```bash
   symfony server:start
   ```

---

## Usage

### Running the Application

After installation, the application will be available at `http://localhost:8000`.

### Default Credentials

- **Admin**:
  - Email: admin@innotravel.tn
  - Password: adminadmin123inno

### Key Routes

- **Front Office**: `http://localhost:8000/user/home`
- **Back Office**: `http://localhost:8000/admin/home`
- **Login**: `http://localhost:8000/login`
- **Registration**: `http://localhost:8000/register`

---

## Contributing

We welcome contributions to the InnoTravel project. Please follow these steps to contribute:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure your code follows the project's coding standards and includes appropriate tests.

---

## License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## Acknowledgments

This project was developed as part of the PIDEV course at **Esprit School of Engineering** under the guidance of our instructors. We would like to thank all contributors and the open-source community for their valuable resources and support.

- Symfony Documentation: [https://symfony.com/doc/current/index.html](https://symfony.com/doc/current/index.html)
- Bootstrap Documentation: [https://getbootstrap.com/docs/](https://getbootstrap.com/docs/)
- AdminLTE Template: [https://adminlte.io/](https://adminlte.io/)
