# CleanMyCar 🚗✨

A modern web application for managing car cleaning service reservations, built with Symfony 8.0.

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Technologies Used](#technologies-used)
- [User Roles](#user-roles)
- [Contributing](#contributing)
- [License](#license)

## 🎯 About

CleanMyCar is a comprehensive car cleaning service management platform that connects customers with service workers. The application allows clients to book car cleaning services, workers to manage their reservations, and administrators to oversee the entire system.

## ✨ Features

### For Clients
- User registration and authentication
- Browse available services
- Create and manage reservations
- View reservation history
- Submit reviews and ratings
- Profile management

### For Workers (Ouvriers)
- Worker profile management
- View and accept reservations
- Manage service zones
- Update availability status
- Track assigned reservations

### For Administrators
- Full admin dashboard with EasyAdmin
- Manage users, clients, and workers
- Oversee all reservations
- View and manage reviews
- System-wide configuration

## 📦 Requirements

- **PHP**: >= 8.4
- **Composer**: Latest version
- **Database**: MySQL, PostgreSQL, or SQLite
- **Web Server**: Apache/Nginx or Symfony CLI
- **Extensions**: 
  - `ext-ctype`
  - `ext-iconv`

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd CleanMyCar/CleanMyCar
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env .env.local
   ```
   Edit `.env.local` and configure your database connection:
   ```env
   DATABASE_URL="mysql://user:password@127.0.0.1:3306/cleanmycar?serverVersion=8.0&charset=utf8mb4"
   ```

4. **Create the database**
   ```bash
   php bin/console doctrine:database:create
   ```

5. **Run migrations**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

6. **Install assets**
   ```bash
   php bin/console importmap:install
   ```

7. **Start the development server**
   ```bash
   symfony server:start
   # or
   php -S localhost:8000 -t public
   ```

Visit `http://localhost:8000` in your browser.

## ⚙️ Configuration

### Database Configuration

Edit `.env.local` to set your database credentials:

```env
DATABASE_URL="mysql://username:password@127.0.0.1:3306/dbname?serverVersion=8.0&charset=utf8mb4"
```

### Security Configuration

The application uses Symfony Security component with role-based access control. User roles are configured in `config/packages/security.yaml`.

### EasyAdmin Configuration

Admin panel is accessible at `/admin` (configured in `config/routes/easyadmin.yaml`).

## 📖 Usage

### Creating Your First Admin User

1. Register a new user through the registration form
2. Manually update the user's role in the database to `ROLE_ADMIN`:
   ```sql
   UPDATE user SET roles = '["ROLE_ADMIN"]' WHERE email = 'your-email@example.com';
   ```

### Accessing Different User Interfaces

- **Public**: `/` - Homepage with services and booking options
- **Client Login**: `/login` - Client authentication
- **Worker Login**: `/login` - Worker authentication (same login, different roles)
- **Admin Login**: `/admin/login` - Admin authentication
- **Admin Panel**: `/admin` - Full admin dashboard (requires ROLE_ADMIN)

## 📁 Project Structure

```
CleanMyCar/
├── assets/              # Frontend assets (JS, CSS)
│   ├── controllers/     # Stimulus controllers
│   └── styles/          # CSS files
├── bin/                 # Console commands
├── config/              # Symfony configuration
│   ├── packages/        # Package configurations
│   └── routes/          # Routing configuration
├── migrations/          # Database migrations
├── public/              # Web root directory
├── src/
│   ├── Controller/      # Application controllers
│   │   ├── Admin/       # Admin controllers
│   │   └── ...          # Other controllers
│   ├── Entity/          # Doctrine entities
│   ├── Form/            # Symfony forms
│   └── Repository/      # Doctrine repositories
├── templates/           # Twig templates
│   ├── admin/           # Admin templates
│   ├── client/          # Client templates
│   ├── ouvrier/         # Worker templates
│   └── components/      # Reusable components
└── tests/               # Test files
```

## 🛠️ Technologies Used

- **Backend Framework**: Symfony 8.0
- **ORM**: Doctrine 3.5
- **Database Migrations**: Doctrine Migrations Bundle
- **Admin Panel**: EasyAdmin Bundle 4.27
- **Templating**: Twig 3.x
- **Frontend**: 
  - Bootstrap (via CDN/components)
  - Stimulus (JavaScript framework)
  - Symfony UX Turbo
  - Asset Mapper
- **Security**: Symfony Security Bundle
- **Forms**: Symfony Form Component
- **Validation**: Symfony Validator Component

## 👥 User Roles

The application supports three user roles:

1. **ROLE_USER** (Client)
   - Default role for registered users
   - Can create reservations
   - Can view their own reservations and profile

2. **ROLE_OVR** (Ouvrier/Worker)
   - Service workers
   - Can view and accept reservations
   - Can manage their profile and availability

3. **ROLE_ADMIN** (Administrator)
   - Full system access
   - Can manage all entities through EasyAdmin
   - Access to admin dashboard

## 🧪 Testing

Run PHPUnit tests:

```bash
php bin/phpunit
```

## 📝 Development

### Creating a New Entity

```bash
php bin/console make:entity
```

### Creating a Migration

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Clearing Cache

```bash
php bin/console cache:clear
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is proprietary. All rights reserved.

## 📞 Support

For support, please open an issue in the repository or contact the development team.

---

**Built with ❤️ using Symfony**

