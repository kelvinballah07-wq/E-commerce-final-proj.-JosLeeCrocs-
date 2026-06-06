# 🧶 JosLee Crocs — Crochet Business Management System

> *Transforming handmade craft into a digital-first experience.*

## 🪡 About the Project

**JosLee Crocs** is a full-featured web-based management system built for a crochet and knitting business founded in 2016, based in Downtown, Kigali. The platform enables customers to browse products, manage their cart, access crochet pattern libraries, shop for yarn supplies, and connect with a knitting community — all from one place.

The system addresses key pain points faced by small handmade businesses:

| Problem | Solution |
|---|---|
| No structured online presence | Full e-commerce-ready web platform |
| No centralized customer dashboard | Role-based dashboards for users & admins |
| Poor brand representation online | Warm, craft-themed custom UI |
| Session & security vulnerabilities | PHP session management with cookie hardening |
| No community touchpoint | Dedicated community connection module |

---

## 🌐 Live Demo

**URL:** [http://josleecrocs.rf.gd](http://josleecrocs.rf.gd)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@gmail.com` | *(as configured in `login_process.php`)* |
| User | *(register via CreateAccount.php)* | — |

> ⚠️ Hosted on InfinityFree (free tier) — occasional downtime may occur.

---

## ✨ Features

### 🔐 Authentication
- Secure email/password login with AJAX (no page reload)
- PHP session management with hardened cookie configuration
- Role detection — auto-redirects admins to the admin dashboard
- Logout with confirmation prompt to prevent accidental sign-outs

### 👤 User Dashboard
- Personalised welcome greeting
- **My Cart** — view and manage knitting products
- **Pattern Library** — browse crochet designs
- **Yarn Shop** — access premium yarns and supplies
- **Community** — connect with fellow knitting enthusiasts

### 👑 Admin Dashboard
- Pattern upload (admin-only)
- User oversight controls
- Redirect-based access control from the user dashboard

### 📄 Supporting Pages
- **Services** — 8 service categories (personalized items, kits, classes, wholesale, and more)
- **About** — company story, vision & mission, co-owner profiles
- **Contact** — phone, email, address, business hours, social media links

---

## 💻 Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (ES6) |
| Backend | PHP 7+ |
| Icons | Font Awesome 6.0 |
| Fonts | Google Fonts (Playfair Display, Poppins, Nunito) |
| Hosting | InfinityFree |
| Version Control | Git & GitHub |
| Deployment | FTP via FileZilla / cPanel |

---

## 📁 Project Structure

```
josleecrocs/
├── Login.php               # Authentication entry point
├── Dashboard.php           # Normal user dashboard
├── admin_dashboard.php     # Admin dashboard (redirect target)
├── Services.php            # Service offerings page
├── About.php               # Company story & mission
├── Contact.php             # Contact information
├── MyProducts.php          # Shopping cart
├── Service.php             # Yarn shop
├── Join_Community.php      # Community link page
├── logout.php              # Session termination
├── clear_session.php       # Session clearing utility
├── login_process.php       # AJAX login handler
├── ForgotPassword.php      # Password recovery (placeholder)
├── CreateAccount.php       # Account registration (placeholder)
├── Style.css               # External stylesheet
└── README.md               # Project documentation
```

---

## 🚀 Getting Started

### Prerequisites

- A web server with **PHP 7+** support (e.g. XAMPP, WAMP, Laragon, or a live PHP host)
- A modern web browser

### Local Setup

```bash
# 1. Clone the repository
git clone https://github.com/josleecrocs/knitting-dashboard.git

# 2. Move into the project folder
cd knitting-dashboard

# 3. Start your local PHP server (e.g. using XAMPP or the built-in PHP server)
php -S localhost:8000

# 4. Open in your browser
# http://localhost:8000/Login.php
```

### Deployment (FTP)

```
Local → Git Commit → Push to GitHub → FTP Upload (FileZilla / cPanel) → InfinityFree Server → Live
```

A GitHub Actions workflow for automated FTP deployment is planned — see [Roadmap](#-roadmap).

---

## 🔑 Authentication & Roles

```
[User visits Login.php]
        ↓
[Submits credentials via AJAX]
        ↓
  login_process.php
        ↓
 ┌──────┴──────┐
 │             │
Success?      Fail?
 │             │
Set Session   Show error
role=user     Stay on Login.php
 or admin
 │
 ↓
role === 'admin'  →  admin_dashboard.php
role === 'user'   →  Dashboard.php
```

Session security is configured with:

```php
session_set_cookie_params(0, '/', '', false, true);
```

---

## 📸 Screenshots

| Page | Description |
|---|---|
| **Login** | Cream gradient background, email/password form, password toggle, decorative knitting card |
| **User Dashboard** | Personalised greeting, four feature cards (Cart, Patterns, Yarn Shop, Community) |
| **Services** | 8 service cards with icons; "Go to Dashboard" button |
| **About** | Company history, vision & mission, co-owner profiles for Leetra S. Gibson & Josie J. Bealdeh |
| **Contact** | Address (Downtown, Kigali), phone (+250 798 696 026), email, social links, map placeholder |

> 📷 Full screenshots are available in the [`/screenshots`](/screenshots) folder (add after capture).

---

## ⚠️ Challenges & Solutions

| # | Challenge | Solution |
|---|---|---|
| 1 | White screen on PHP files | Ensured `.php` extension and PHP-enabled server |
| 2 | Sessions not persisting | Added `session_set_cookie_params()` with correct flags |
| 3 | Admins landing on user dashboard | Role check at top of `Dashboard.php` with redirect |
| 4 | Page reload on login submit | Replaced form POST with `fetch()` API + `preventDefault()` |
| 5 | Mobile nav overlap | CSS media queries for `.user-menu` below 768px |
| 6 | External CSS loading delays | Critical CSS embedded per page; external as fallback |
| 7 | Accidental logouts | `confirm()` dialog before calling `clear_session.php` |

---

## 🗺️ Roadmap

### Short-term (Next 3 months)
- [ ] MySQL database integration for users, products, and orders
- [ ] Full product management CRUD in admin panel
- [ ] Shopping cart persistence across sessions
- [ ] Order history tracking
- [ ] Email notifications (order confirmations, password reset)
- [ ] Real pattern library with PDF upload & preview

### Medium-term (6–9 months)
- [ ] Stripe / PayPal payment integration
- [ ] Self-service user registration with email verification
- [ ] Product reviews and ratings
- [ ] Search and filter (category, price, yarn type)
- [ ] Wishlist feature
- [ ] Mailchimp newsletter integration

### Long-term (1+ year)
- [ ] 📱 React Native mobile app
- [ ] 🎥 Embedded video crochet tutorials
- [ ] 👥 Community discussion forum
- [ ] 📊 Admin analytics dashboard
- [ ] 🌍 Multi-language support (English, French, Kinyarwanda)
- [ ] 🤖 AI-powered pattern recommendations
- [ ] 🧪 PHPUnit + Jest automated testing
- [ ] 🐳 Docker containerisation

---

## 🤝 Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Commit your changes: `git commit -m "Add: your feature description"`
4. Push to the branch: `git push origin feature/your-feature-name`
5. Open a Pull Request

Please follow existing code style and comment your PHP/JS clearly.

---

## 📬 Contact

**JosLee Crocs Inc.**

| | |
|---|---|
| 📍 Address | Downtown, Kigali, Rwanda |
| 📞 Phone | +250 798 696 026 / +231 555 723 496 |
| 📧 Email | josleecrocs@gmail.com |
| 🌐 Website | [josleecrocs.rf.gd](http://josleecrocs.rf.gd) |

**Co-founders:** Leetra S. Gibson & Josie J. Bealdeh

---

<div align="center">

Made with 🧶 and ❤️ by the JosLee Crocs team · Est. 2016 · Kigali, Rwanda

</div>
