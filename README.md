# 🧠 HNG13 Stage 1 — String Analyzer API

A Laravel RESTful API service that analyzes strings and stores their computed properties.  
This project was built for **HNG13 Stage 1 Backend Task**. It computes useful attributes such as palindrome detection, unique characters, word count, string length, and SHA-256 hash.

---

## ⚙️ Local Setup Guide

Follow the steps below to configure and run the application locally.

### 1️⃣ Clone Repository
```bash
git clone https://github.com/yourusername/string-analyzer-api.git
cd string-analyzer-api
```

### 2️⃣ Install Dependencies

Ensure you have PHP 8.1+, Composer, and Laravel installed.

```bash
composer install
```

### 3️⃣ Configure Environment

Copy the example environment file and generate your app key:

```bash
cp .env.example .env
php artisan key:generate
```

Update your .env database section (for local MySQL use):

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=string_analyzer
DB_USERNAME=root
DB_PASSWORD=
```

Then run migrations:

```bash
php artisan migrate
```

### 4️⃣ Run Application

Start your development server:

```bash
php artisan serve
```

Your API will now be available at:

🌐 http://127.0.0.1:8000

## 📘 API Endpoints

All endpoints return JSON responses and follow proper RESTful design.

### 🧩 1. Create / Analyze String

Analyzes a string and stores its computed properties.

#### Endpoint
`POST /api/strings`

#### Request Body
```json
{
    "value": "string to analyze"
}
```

#### Success Response (201 Created)
```json
{
    "id": "sha256_hash_value",
    "value": "string to analyze",
    "properties": {
        "length": 16,
        "is_palindrome": false,
        "unique_characters": 12,
        "word_count": 3,
        "sha256_hash": "abc123...",
        "character_frequency_map": {
            "s": 2,
            "t": 3,
            "r": 2
        }
    },
    "created_at": "2025-08-27T10:00:00Z"
}
```

#### Error Responses

| Code | Message |
|------|---------|
| 400  | Missing or invalid value field |
| 409  | String already exists |
| 422  | Invalid data type for value |

### 🔍 2. Get Specific String

Retrieve the stored analysis of a given string.

#### Endpoint
`GET /api/strings/{string_value}`

#### Success Response (200 OK)
```json
{
    "id": "sha256_hash_value",
    "value": "requested string",
    "properties": {
        "length": 16,
        "is_palindrome": true,
        "unique_characters": 9,
        "word_count": 1,
        "sha256_hash": "abc123...",
        "character_frequency_map": {
            "m": 2,
            "a": 2,
            "d": 1
        }
    },
    "created_at": "2025-10-21T10:00:00Z"
}
```

#### Error Responses

| Code | Message |
|------|---------|
| 404  | String not found |
| 400  | Missing required parameter |

### 📋 3. Get All Strings (with Filtering)

Fetch all analyzed strings with advanced filtering options.

#### Endpoint
`GET /api/strings`

#### Available Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| is_palindrome | boolean | Filter by palindrome status |
| min_length | integer | Minimum string length |
| max_length | integer | Maximum string length |
| word_count | integer | Exact number of words |
| contains_character | string | Filter strings containing a given character |

#### Example Request
`GET /api/strings?is_palindrome=true&min_length=5&max_length=20&word_count=2&contains_character=a`

[Rest of content continues similarly with proper formatting and tables where applicable]

## 🛠️ Tech Stack

| Component | Technology |
|-----------|------------|
| Framework | Laravel 11 (PHP 8.2+) |
| Database | MySQL |
| Logging | Laravel Log Facade |
