# 🚗 Gestion de Parc Automobile

A full-stack fleet management web application built with **Symfony 6**, **MySQL**, and **Bootstrap 5**.

## ✨ Features
- Secure authentication with role-based access (Admin / Driver)
- Full CRUD for vehicles, assignments, and maintenance records
- Admin dashboard with real-time statistics
- Drivers can only view their own assignments
- CSRF protection on all delete operations

## 🛠️ Tech Stack
- **Backend:** PHP 8, Symfony 6, Doctrine ORM
- **Frontend:** Twig, Bootstrap 5
- **Database:** MySQL
- **Tools:** Git, Symfony CLI, phpMyAdmin

## 📦 Installation Steps

```bash
# Clone the project
git clone https://github.com/inassechafi/gestion-parc-automobile.git

# Navigate to the project folder
cd gestion-parc-automobile

# Install dependencies
composer install

# Configure the database in .env or .env.local
DATABASE_URL="mysql://root:@127.0.0.1:3306/gestion_parc"

# Create the database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Start the server
symfony server:start
