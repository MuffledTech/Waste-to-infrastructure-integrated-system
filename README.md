# Waste to Infrastructure Management System

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/YourUsername/waste-system)](https://github.com/YourUsername/waste-system/issues)
[![GitHub stars](https://img.shields.io/github/stars/YourUsername/waste-system)](https://github.com/YourUsername/waste-system/stargazers)

A web-based platform that allows citizens to report waste, collectors to manage pickups, and administrators to fund and monitor infrastructure projects using the generated revenue.

---

## 🔹 Features

### Citizen

* Report waste with photos.
* Track waste pickup status.
* Earn points for reporting.

### Collector

* View pending waste reports.
* Accept jobs and upload verification.
* Efficient waste collection management.

### Admin

* Approve citizen reports.
* Analytics dashboard with charts.
* Create and manage infrastructure projects.

---

## 💻 Technologies Used

* **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript
* **Backend:** PHP
* **Database:** MySQL
* **Optional Libraries:** Chart.js for analytics

---

## ⚙️ Setup Instructions

### 1. Prerequisites

* **XAMPP:** [Download here](https://www.apachefriends.org/)
* **Browser:** Chrome, Firefox, or Edge

### 2. Place Project Files

Copy the `waste_system` folder to your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\waste_system
```

### 3. Configure Database

1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin`.
3. Click **New** → Create a database named `waste_management_system`.
4. Click **Import** → Select `waste_system/database.sql` → Click **Go**.

### 4. Setup Offline Assets (Optional)

* Run `setup_assets.bat` in the `waste_system` folder to download Bootstrap and Chart.js locally.
* Manual download locations if needed:

  * Bootstrap CSS → `assets/css/bootstrap.min.css`
  * Bootstrap JS → `assets/js/bootstrap.bundle.min.js`
  * Chart.js → `assets/js/chart.js`

### 5. Run the System

* Visit [http://localhost/waste_system](http://localhost/waste_system)

---

## 📝 Default Login Credentials

| Role      | Email                                             | Password     |
| --------- | ------------------------------------------------- | ------------ |
| Admin     | [admin@waste.gov](mailto:admin@waste.gov)         | admin123     |
| Collector | [collector@waste.gov](mailto:collector@waste.gov) | collector123 |
| Citizen   | [citizen@waste.gov](mailto:citizen@waste.gov)     | citizen123   |

---

## 🛠 Troubleshooting

* **Database Connection Error:** Check `config/db.php` and update MySQL credentials.
* **Uploads Not Working:** Ensure the `uploads` folder exists and has write permissions.

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

## 🤝 Contribution

* Fork the repository
* Create a branch for your feature (`git checkout -b feature-name`)
* Commit your changes (`git commit -m 'Add feature'`)
* Push to the branch (`git push origin feature-name`)
* Create a Pull Request

---

## 🌐 Live Demo (Optional)

* You can host a demo using XAMPP or link your live server here.
