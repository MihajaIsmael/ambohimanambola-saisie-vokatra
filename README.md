# Saisie Ticket POS80 - Fandraisana Vokatra

A lightweight, high-performance web-based Point of Sale (POS) and management system designed for church community event donations (*Vokatra*). This application streamlines the process of searching or quickly creating community members (*Mpivavaka*), logging multi-product items, and generating immediate thermal receipts optimized for 80mm printers (POS80).

---

## 🚀 Features

*   **Real-time AJAX Autocomplete:** Instantly search for community members by name.
*   **1-Click Quick Member Creation:** If a member doesn't exist, create them instantly with a background auto-incremented numerical ID and select them automatically without reloading.
*   **Dynamic Product Grid:** Seamlessly add or remove multiple item rows (Product Name, Quantity, Price) inside a single transaction.
*   **Multi-Event Framework:** Isolate data streams based on unique active events, complete with automated fallback setup triggers.
*   **Live Metrics Sidebar:** Side-by-side feed layout displaying the 5 latest real-time scans/receipts printed.
*   **Advanced Logs Dashboard:** Dedicated history view featuring dynamic pagination (10 to 100 rows per page) and filtering by event.
*   **POS80 Print System:** Custom boundary window triggers perfectly dimensioned for 80mm thermal receipt roll structures.

---

## 🛠️ Tech Stack

*   **Backend:** PHP 8.0+ (Strict typing, streamlined logic)
*   **Database:** MongoDB (NoSQL schema-less flexibility for complex transaction payloads)
*   **Dependency Management:** Composer (PSR-4 compliant autoloader orchestration)
*   **Frontend:** Vanilla PHP / JavaScript (ES6+ Fetch API), Semantic HTML5, and responsive custom CSS grid layouts.

---

## ⚙️ Installation & Setup

### 1. Prerequisites
Ensure you have the following installed on your host machine:
* Docker
* Docker Compose

### 2. Clone the Repository
Clone this repository and navigate into the project root directory:
```bash
git clone https://github.com/MihajaIsmael/ambohimanambola-saisie-vokatra.git
```
```bash
cd ambohimanambola-saisie-vokatra
```
### 3. Environment Variables Configuration
Create a **.env** file in the root directory to map your local environment variables. These will be automatically injected into your Docker containers:

**Extrait de code**
```
DB_HOST=hostname
DB_PORT=111111
DB_ROOT_USER=rootuser
DB_ROOT_PASSWORD=rootpassword
DB_NAME=app-database
DB_USER=app-user
DB_PASSWORD=app-password

COLLECTION_NAME=harvests
COLLECTION_USERS=members
COLLECTION_SETTINGS=settings
MONGO_URI=mongodb://mongodb:111111
```
 *Note: In a Docker Compose network, use mongodb (the service name) as the hostname instead of localhost.*

### 4. Build and Start the Containers
Run Docker Compose to build your PHP environment (including the MongoDB extension) and spin up the database service:
```bash
docker compose up -d --build
```

## 🗄️ Database Schema & Collections
The application maps objects directly into three core collections under your designated MongoDB database:

* [COLLECTION_SETTINGS]: Stores configurations, event infos (event_name, country_code, year_code, event_id, updated_at).

* [COLLECTION_USERS]: Tracks registered members (id as an incremental integer, name, created_at).

* [COLLECTION_NAME]: Logs item scans, amounts, references, and timestamps (printed_at, event_setting_id).

## 📄 License
This project is proprietary and customized for localized implementation structures. All rights reserved.