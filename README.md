
# VoteSecure – Online Voting Management System

VoteSecure is a web-based online voting management system developed using PHP, MySQL, HTML, CSS and JavaScript.

The application provides a platform for voter registration, authentication, election management, candidate management and online voting.

## Features

- Voter registration and login
- Administrator authentication
- Voter profile management
- Election management
- Candidate management
- Online vote submission
- Prevention of multiple votes in the same election
- Election results and reports
- Database-driven application

## Technologies Used

- HTML5
- CSS3
- JavaScript
- PHP
- MySQL
- PDO (PHP Data Objects)

## Project Structure

```text
VoteSecure/
│
├── api/
│   ├── auth/
│   ├── candidates/
│   ├── config/
│   ├── elections/
│   ├── results/
│   ├── vote/
│   ├── voters/
│   └── helpers.php
│
├── css/
│   └── style.css
│
├── js/
│   └── app.js
│
├── admin.html
├── candidates.html
├── dashboard.html
├── db.sql
├── home.html
├── login.html
├── profile.html
├── register.html
└── README.md
```

## Requirements

- PHP 8.0 or later
- MySQL
- Apache server (XAMPP recommended)
- Web browser

## Installation and Setup

1. Install XAMPP.
2. Copy the VoteSecure project folder into the `htdocs` directory.
3. Start Apache and MySQL from the XAMPP Control Panel.
4. Open phpMyAdmin.
5. Create/import the database using `db.sql`.
6. Configure database connection settings in `api/config/db.php`.
7. Open the application in your browser:

   http://localhost/VoteSecure/home.html

## Database Configuration

The application uses MySQL with PDO for database connectivity.

The database settings can be configured using these environment variables:

- DB_HOST
- DB_NAME
- DB_USER
- DB_PASS

For local development, the application has fallback settings in `api/config/db.php`.

## Demo Administrator Account

The SQL setup includes a demonstration administrator account.

For security reasons, demo login credentials are not published here. Replace the default demo credentials before deploying the application publicly.

## Database Tables

- users – Stores administrator and voter information.
- elections – Stores election details.
- candidates – Stores candidate information.
- votes – Stores submitted votes.

## How It Works

1. Voters register through the registration page.
2. Users log in through the authentication system.
3. Administrators manage elections and candidates.
4. Eligible voters participate in active elections.
5. The application stores submitted votes in MySQL.
6. Results and reports can be viewed through the application.

## Security Considerations

- Use strong passwords for administrator accounts.
- Configure database credentials securely.
- Do not publish private credentials or real user information.
- Use HTTPS when deploying the application.
- Review authentication and authorization before production use.

## Future Enhancements

- Email verification
- Two-factor authentication
- Improved audit logging
- Enhanced security monitoring
- Responsive mobile interface

## Author

Developed as an academic project.

## Disclaimer

VoteSecure is an academic demonstration project. It should not be used for official or public elections without independent security auditing, privacy review and appropriate safeguards.
