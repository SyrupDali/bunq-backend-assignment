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

## Features

- **User Management**: Create and manage users with unique usernames.
- **Group Management**: Create groups and allow users to join them.
- **Messaging**: Send and check messages in group chats.
- **Error Handling**: Proper error messages and status codes for API requests.
  
## Technologies Used

- **PHP**: Server-side scripting language.
- **SQLite**: Lightweight database engine used to store user and group data.
- **Postman**: Tool for testing API endpoints.

## Installation

1. **Clone the repository**:

   ```bash
   git clone https://github.com/yourusername/group-messaging-app.git
   cd group-messaging-app

2. **Install PHP dependencies**:

   ```bash
    composer install

3. **Start the PHP server**:

    ```bash
    php -S localhost:8000 -t public
## API Specification
check the API specification [here](api_spec.yaml)

## Automated Tests

1. **Change line 8 in public/index.php to**: 
    
    ```php

    $pdo = (new SQLiteConnection())->connect(true);

2. **Then start the PHP server and run the tests using the following command under root directory**:

    ```bash
    ./vendor/bin/phpunit tests/ApiTest.php

3. **Change line 8 in public/index.php back to**: 
    
    ```php
    $pdo = (new SQLiteConnection())->connect();