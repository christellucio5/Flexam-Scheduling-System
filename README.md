# FLEXAM — Web-Based Exam Scheduling System

FLEXAM is a web-based platform for managing exam scheduling, room allocation, proctor assignments, and course/college administration. Built to streamline the exam coordination process for academic institutions.

## Features
- Exam schedule creation and management
- Room and proctor assignment
- Course and college data management
- User role management (Admin, Head, Guest access levels)
- Import tools for bulk data (courses, rooms, proctors, schedules, colleges)
- Audit logging and archive/semester tracking
- Feedback collection system

## Tech Stack
- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Local environment:** XAMPP

## My Role: Developer
- Designed and developed the FLEXAM system from the ground up, including exam scheduling logic, room/proctor assignment, and admin dashboard functionality
- Built the database structure and backend logic using PHP and MySQL
- Implemented data import tools for courses, rooms, proctors, and schedules
- Handled testing, deployment, and troubleshooting throughout development

## Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP)
- Git

### Setup
1. Clone the repository into your `htdocs` folder:

git clone https://github.com/christellucio5/Flexam-Scheduling-System.git

2. Copy `db/Db.example.php` to `db/Db.php` and update it with your local MySQL credentials:
```php
   $host = "localhost";
   $dbname = "your_database_name";
   $username = "your_db_username";
   $password = "your_db_password";
```
3. Import the database schema (see `db/migrations/`) into MySQL via phpMyAdmin or the command line.
4. Start Apache and MySQL in XAMPP.
5. Visit `http://localhost/flexam` in your browser.

## Note
Database credentials and live data files are excluded from this repository for security. See `db/Db.example.php` for the expected configuration format.