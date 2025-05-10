# Camping Site Project

A Symfony-based web application for managing camping events and user participation. This platform allows users to create, join, and manage camping events while providing administrators with tools to oversee and moderate the platform.

## Features

### User Features
- User registration and authentication
- Profile management
- Create and manage camping events
- Join/leave events
- View event details and status
- Track created and joined events
- Edit profile information

### Admin Features
- Comprehensive dashboard with statistics
- User management
- Event approval system
- Event status monitoring
- User activity tracking
- Event distribution analytics

## Technical Stack

- **Framework**: Symfony 6.x
- **Database**: MySQL 8.0
- **Frontend**: 
  - Bootstrap 5
  - Bootstrap Icons
  - Twig Templates
- **Authentication**: Custom PlainText Authenticator
- **File Upload**: Symfony File Upload Component

## Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 8.0 or higher
- Symfony CLI (optional)

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd camping_site
```

2. Install dependencies:
```bash
composer install
```

3. Configure your database in `.env`:
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/camp?serverVersion=8.0"
```

4. Create the database:
```bash
php bin/console doctrine:database:create
```

5. Run migrations:
```bash
php bin/console doctrine:migrations:migrate
```

6. Create upload directory for event images:
```bash
mkdir -p public/uploads/events
chmod 777 public/uploads/events
```

7. Start the Symfony development server:
```bash
symfony server:start
```

## Project Structure

```
camping_site/
├── config/                 # Configuration files
├── public/                 # Public directory
│   └── uploads/           # Uploaded files
├── src/
│   ├── Controller/        # Controllers
│   ├── Entity/           # Database entities
│   ├── Form/             # Form types
│   ├── Repository/       # Entity repositories
│   └── Security/         # Security configuration
├── templates/             # Twig templates
└── var/                  # Cache and logs
```

## User Roles

- **ROLE_USER**: Regular user with event creation and participation capabilities
- **ROLE_ADMIN**: Administrator with full system access and moderation capabilities

## Event Management

Events go through the following statuses:
- **Pending**: Awaiting admin approval
- **Approved**: Available for user participation
- **Rejected**: Not approved by admin

## Security

- Custom authentication system
- Role-based access control
- CSRF protection
- Secure file upload handling
- Password hashing

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the repository or contact the development team.

## Acknowledgments

- Symfony Framework
- Bootstrap
- All contributors and users of the platform 