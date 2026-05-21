# DestinyGate Institute System Startup Guide

This document provides instructions on how to start and run the DestinyGate Institute system, featuring a Laravel PHP backend and a React/TypeScript/Vite frontend.

---

## 🚀 Quick Start (Recommended)

To start both the Laravel backend and Vite frontend dev servers simultaneously, you can run a single command from either the root folder or the Laravel folder.

### Option A: From the Root Directory (Recommended)
Open a terminal in the project root directory and run:
```bash
npm start
```
*(Or `pnpm start` / `npm run dev` depending on your preferred package manager).*

### Option B: From the `destiny-gate-laravel` Directory
Navigate into the Laravel folder and run:
```bash
cd destiny-gate-laravel
npm start
```

---

## 🌐 Accessing the Application

Once the command is running, you can access the system at:

- **Web Application Portal:** [http://127.0.0.1:8000](http://127.0.0.1:8000)
- **Vite Frontend Development Asset Server:** [http://localhost:5173](http://localhost:5173) *(runs in the background to serve assets hot-reloaded)*

---

## 🛠️ Architecture & Under-the-Hood Details

When you run `npm start`, the system executes the following:
1. **Laravel Web Server (`php artisan serve`)**: Serves the PHP endpoints, routing, and renders the initial SPA entry layout.
2. **Vite Development Server (`vite`)**: Compiles and delivers React components (TypeScript/JSX) dynamically to the page with Hot Module Replacement (HMR).

### Useful Manual Commands (Run from `destiny-gate-laravel`):

| Command | Purpose |
| :--- | :--- |
| `php artisan serve` | Starts only the Laravel backend server. |
| `npm run dev` | Starts only the Vite asset server. |
| `php artisan migrate` | Runs any pending database migrations. |
| `php artisan migrate:status` | Checks which migrations have been executed. |
| `php artisan db:seed` | Seeds the database with mock/essential data. |

---

## 📁 Project Directory Structure

- `destiny-gate-laravel/`: Contains the complete Laravel backend.
  - `app/`: PHP Controllers, Models, and Middlewares.
  - `routes/web.php` & `routes/api.php`: Backend routes.
  - `resources/tsx/`: **React/TypeScript frontend application** (pages, hooks, components, styles).
  - `resources/views/spa.blade.php`: The Blade layout file rendering the React entry point.
