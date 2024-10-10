# bunq-backend-assignment
A simple group messaging application that allows users to create groups, join existing groups, and send messages to those groups. Built using PHP and SQLite.

# Group Messaging App

A simple group messaging application that allows users to create groups, join existing groups, and send messages to those groups. Built using PHP and SQLite.

## Table of Contents

- [Features](#features)
- [Technologies Used](#technologies-used)
- [Installation](#installation)
- [API Specification](#api-specification)
- [Automated Tests](#automated-tests)
- [Running the Application](#running-the-application)

## Features

- **User Management**: Create and manage users with unique usernames.
- **Group Management**: Create groups and allow users to join them.
- **Messaging**: Send and check messages in group chats.
- **Error Handling**: Proper error messages and status codes for API requests.
  
## Technologies Used

- **PHP**: Server-side scripting language.
- **SQLite**: Lightweight database engine used to store user and group data.
- **Slim Framework**: PHP micro-framework used to build the API.
- **PHPUnit**: Testing framework for PHP.
- **Postman**: Tool for testing API endpoints.

## Installation

1. **Clone the repository**:

   ```bash
   git clone https://github.com/SyrupDali/bunq-backend-assignment.git
   cd bunq-backend-assignment

2. **Install PHP dependencies**:

   ```bash
    composer install

## API Specification
check the API specification [here](api_spec.yaml)

## Automated Tests

1. **Run the tests using the following command under root directory**:

    ```bash
    ./vendor/bin/phpunit tests

## Running the Application

1. **Start the PHP server**:

    ```bash
    php -S localhost:8000 -t public

2. **Use Postman or cURL to test the API endpoints**:
    
    Example cURL commands:
    
    ```bash
    # Get all users
    curl -X GET http://localhost:8000/users
    # Create a new user
    curl -X POST http://localhost:8000/users -d '{"username": "john_doe"}'