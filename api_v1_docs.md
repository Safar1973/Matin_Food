# REST API v1 Implementation for Matin Food

This project now supports a formal REST API starting at version 1.

## Base URL
`https://matin-food.local/api/v1`

## 1. Directory Structure
```text
C:\xampp\htdocs\Matin_Food\
├── .htaccess                 # Main routing / URL rewriting
└── api\
    └── v1\
        ├── index.php         # Central API Router
        ├── controllers\
        │   ├── ProductController.php
        │   ├── OrderController.php
        │   └── AiController.php
```

## 2. API Endpoints

### Products
- **`GET /api/v1/products`**: Lists all products (optional: `?lang=de|en|ar`).
- **`GET /api/v1/products/count`**: Returns total product count.
- **`GET /api/v1/products/{id}`**: Returns details for a specific product.

### Orders
- **`GET /api/v1/orders`**: Lists recent orders (limited to 50).
- **`POST /api/v1/orders`**: Places a new order. Requires JSON body with items, customer details, and total amount.

### AI (Gemini)
- **`POST /api/v1/ai`**: Generates product descriptions. Requires `product_name` and `api_key` in JSON body.

### System
- **`GET /api/v1/health`**: Returns API status and version info.

## 3. Configuration Details

- **Routing**: Handled by Apache's mod_rewrite via the root `.htaccess`.
- **Response Format**: All responses are JSON (UTF-8).
- **Error Handling**: Standard HTTP status codes (200, 400, 404, 405, 500) are returned with descriptive error messages.
- **CORS**: Enabled for all origins (`*`) to facilitate frontend integration.
