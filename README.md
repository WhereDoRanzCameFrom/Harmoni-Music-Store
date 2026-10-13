# 🎸 Harmoni Music Store - E-Commerce Platform

A specialized e-commerce web application designed for **Harmoni Music Store** to sell guitars, basses, and musical accessories. Built using native **PHP**, **MySQL**, and **Bootstrap**, this platform delivers a seamless experience for musicians while providing robust management tools for store operators.

# *Some of this project's code is kept in a private repository for security and copyright reasons.*

## 🚀 Key Features Overview

# 🛒 Customer Storefront
# - Multi-Category Catalog: Browse acoustic guitars, electric guitars, bass guitars, and accessories with real-time filtering by price and brand.
# - Dynamic Product Specifications: Detailed instrument specs including wood types, pickups, fret counts, and scale lengths.
# - Interactive Cart & Checkout: Streamlined order processing with real-time stock verification and shipping cost estimation.
# - User Account Portal: Secure customer registration, login, and historical order tracking.

# 🛠️ Store Administration Panel
# - Executive Dashboard: Real-time visual metrics for total revenue, recent sales, low-stock alerts, and user registrations.
# - Inventory Management (CRUD): Full control over adding, updating, and archiving guitar/bass listings and high-res photo assets.
# - Order Pipeline Control: Track and update order statuses (Pending Payment, Processing, Shipped, Completed).
# - Category & Brand Taxonomy: Flexible organization for emerging guitar manufacturers and distinct instrument models.

---

## 🔒 Security & Proprietary Notice

> **Note on Code Concealment & Repository Privacy:**
> 
> To protect **Harmoni Music Store**'s commercial assets, production security, and customer privacy, certain components of this source code have been omitted or obscured in this public repository:
> 
> - **Database Credentials & API Keys:** Files containing database access vectors, payment gateway API keys (`/config/db.php`, `/config/payment.php`), and secret environment variables (`.env`) are excluded via `.gitignore`.
> - **Proprietary Payment Gateway Logic:** Core payment processing webhooks and merchant authentication routines are replaced with mockup interfaces to prevent unauthorized cloning or fraud exploits.
> - **Internal Administrative Routes:** Specific sensitive administrative endpoints and automated cron-job scripts for inventory reconciliation have been hidden to comply with security best practices.
> 
> *A full, un-redacted repository is available upon request for authorized security auditors or core maintenance developers.*

---

## 🛠️ Tech Stack

- **Backend:** PHP (Native / Procedural architecture)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
- **Icons:** FontAwesome 6 / Bootstrap Icons
- **Server Environment:** Apache (XAMPP / WampServer / Linux LAMP Stack)

---

## ⚙️ Local Installation & Setup

1. **Clone the Repository:**
   Move the project into your local server root directory (`htdocs` or `www`):
   ```bash
   cd C:/xampp/htdocs/
   git clone [https://github.com/your-username/harmoni-music-store.git](https://github.com/your-username/harmoni-music-store.git)
