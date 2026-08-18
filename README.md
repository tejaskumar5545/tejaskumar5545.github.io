# EngiHub — Engineering Student Hub

A full-featured engineering student portal with a modern glassmorphism UI. Deploy as **GitHub Pages** (static) or as a **PHP + MySQL** web app with full admin panel, student authentication, MCQ exams, and file management.

---

## Features

- **Notes Management** — Upload, browse, search & download PDF notes by semester and branch
- **Syllabus, PYQ, Practicals** — Previous year papers, syllabus PDFs, practical files
- **Assignments** — Upload assignments with deadlines
- **Coding Practice** — Coding problems with solutions
- **Projects & Placement** — Project ideas, placement drives & job openings
- **MCQ Online Exams** — Create exams, add questions, student attempts with instant results
- **Gallery** — Campus photo gallery with lightbox
- **Notices** — Announcements with important flag
- **Student Registration & Login** — Student accounts with profile, download history
- **Admin Dashboard** — Full admin panel to manage all content types
- **Admissions** — Online admission form
- **Contact Form** — Student inquiries
- **Mobile Responsive** — Works on all devices
- **WhatsApp Integration** — Floating chat button

---

## Deploy to GitHub Pages (Static)

GitHub Pages serves only static files (HTML, CSS, JS). All 20 public pages are pre-converted to `.html` with placeholder content — just push and go.

### Step 1: Create GitHub Repository

1. Go to [github.com/new](https://github.com/new)
2. Repository name: `EngiHub` (or any name you like)
3. Set to **Public**
4. Click **Create repository**

### Step 2: Push Your Code

```bash
cd H:\xampp\htdocs\CollageNotes

git init
git add .
git commit -m "Initial commit: EngiHub student portal"
git remote add origin https://github.com/YOUR_USERNAME/EngiHub.git
git branch -M main
git push -u origin main
```

### Step 3: Enable GitHub Pages

1. Go to your repository on GitHub
2. Click **Settings** (tab)
3. Scroll down to **Pages** (left sidebar)
4. Under **Source**, select **Deploy from a branch**
5. Under **Branch**, select `main` and `/ (root)`
6. Click **Save**
7. Wait 1-2 minutes — your site will be live!

**Your live URL:** `https://YOUR_USERNAME.github.io/EngiHub/`

### What Works on GitHub Pages

| Feature | Status |
|---------|--------|
| Homepage, About, Contact | Works |
| Browse Notes, Syllabus, PYQ, Practical, Coding, Projects, Placement, Assignments | Works (static filters, placeholder content) |
| Gallery | Works (static) |
| Notices | Works (static) |
| Online Tests / MCQ Exams | Works (static, no login) |
| Student Login / Register | Works (forms shown, no backend) |
| Admin Login | Works (form shown, no backend) |
| Upload, Download, Dashboard | **Does not work** (requires PHP + MySQL) |

### Limitations

- No database — all content is static placeholder
- Login/Register forms are shown but have no backend processing
- Admin/Student dashboards are not included (require session auth)
- File upload/download requires PHP backend
- To make it fully functional, deploy on a PHP host (see below)

---

## Deploy to PHP Hosting (Full Functionality)

### Requirements

- **PHP** 7.4 or higher (with `mysqli` extension)
- **MySQL** 5.7+ or **MariaDB** 10.4+
- **Apache** web server (XAMPP, WAMP, or live hosting)

### Option 1: InfinityFree (Free PHP Hosting)

1. Create account at [infinityfree.com](https://infinityfree.com)
2. Create a MySQL database from the control panel
3. Note the database credentials (host, user, password, db name)
4. Upload all project files via File Manager or FTP
5. Create `config.php` on the server with your InfinityFree credentials:

```php
<?php
$db_host = "sql1XX.infinityfree.com";
$db_user = "if0_XXXXXXXX";
$db_password = "YOUR_PASSWORD";
$db_name = "if0_XXXXXXXX_your_database";
?>
```

6. Import `database/schema.sql` and `database/seed.sql` via phpMyAdmin
7. Create `uploads/`, `uploads/gallery/`, `uploads/notes/` directories

### Option 2: Any PHP Hosting (cPanel, Hostinger, etc.)

Same steps as InfinityFree — just use your hosting provider's database credentials.

### Option 3: VPS / Cloud (DigitalOcean, AWS, etc.)

```bash
# On your VPS
sudo apt update && sudo apt install php mysql-server apache2
git clone https://github.com/YOUR_USERNAME/EngiHub.git /var/www/html/EngiHub
cd /var/www/html/EngiHub
cp config.example.php config.php
# Edit config.php with your credentials
mysql -u root -p < database/schema.sql
mysql -u root -p college_notes < database/seed.sql
```

### Local XAMPP Setup

```bash
# 1. Clone
git clone https://github.com/YOUR_USERNAME/EngiHub.git
# Or copy to C:\xampp\htdocs\EngiHub\

# 2. Configure
cp config.example.php config.php
# Edit config.php with your local credentials

# 3. Create database
mysql -u root -p -e "CREATE DATABASE college_notes"
mysql -u root -p college_notes < database/schema.sql
mysql -u root -p college_notes < database/seed.sql

# 4. Start XAMPP, open http://localhost/EngiHub/
```

---

## Database Setup (PHP Hosting Only)

### Tables (17 total)

| Table | Purpose |
|-------|---------|
| `admin` | Admin login credentials |
| `students` | Student registrations |
| `notes` | Uploaded PDF notes |
| `syllabus` | Syllabus PDFs |
| `pyq` | Previous year question papers |
| `practicals` | Practical files |
| `assignments` | Assignment files with deadlines |
| `coding` | Coding practice problems |
| `projects` | Project reports |
| `placement` | Placement drives & job postings |
| `notices` | Announcements |
| `gallery_images` | Campus photos |
| `exams` | MCQ exam definitions |
| `questions` | Exam questions |
| `exam_attempts` | Student exam submissions |
| `student_answers` | Individual answer records |
| `downloads` | Download history tracking |
| `admissions` | Admission form submissions |
| `contact_messages` | Contact form messages |

---

## Project Structure

```
EngiHub/
├── index.html              # Homepage (GitHub Pages entry point)
├── about.html              # About page
├── notes.html              # Browse notes
├── syllabus.html           # Browse syllabus
├── pyq.html                # Previous year papers
├── practical.html          # Practical files
├── coding.html             # Coding practice
├── projects.html           # Projects
├── placement.html          # Placement corner
├── assignments.html        # Assignments
├── exams.html              # MCQ online tests
├── notices.html            # College notices
├── gallery.html            # Campus gallery
├── contact.html            # Contact form
├── login.html              # Admin login
├── student_login.html      # Student login
├── register.html           # Student registration
├── subjects.html           # Browse by subject
├── pdfs.html               # PDF downloads
├── admission.html          # Admission form
├── database/
│   ├── schema.sql          # Database structure (17 tables)
│   └── seed.sql            # Sample data + default admin
├── images/                 # Static images (logo, etc.)
├── js/
│   └── main.js            # Frontend JavaScript
├── uploads/                # User-uploaded files (gitignored)
│   ├── gallery/           # Gallery images
│   └── notes/             # PDF notes
├── *.php                   # PHP source files (backend functionality)
├── config.example.php      # Credential template (safe to commit)
├── config.php             # Actual credentials (gitignored)
├── .env.example           # Environment variable template
├── .gitignore             # Git ignore rules
├── .htaccess              # Apache config (caching, compression)
├── style.css              # All styles
├── robots.txt             # SEO
├── sitemap.xml            # SEO
└── README.md              # This file
```

---

## Git Commands

```bash
# Initialize (if not already)
git init

# Add all files
git add .

# Check what will be committed
git status

# Commit
git commit -m "Initial commit: EngiHub student portal"

# Add remote
git remote add origin https://github.com/YOUR_USERNAME/EngiHub.git

# Push
git branch -M main
git push -u origin main
```

---

## Files Changed for GitHub

| File | Change |
|------|--------|
| `db.php` | Now loads credentials from `config.php` instead of hardcoding |
| `.gitignore` | Enhanced to exclude sensitive files |
| `test.php` | **Deleted** (was exposing database passwords) |

## New Files

| File | Purpose |
|------|---------|
| `*.html` (20 files) | Static versions of all public pages for GitHub Pages |
| `config.example.php` | Credential template (safe to commit) |
| `.env.example` | Environment variable template |
| `database/schema.sql` | Clean database structure (no user data) |
| `database/seed.sql` | Sample data with placeholder admin password |
| `uploads/.gitkeep` | Preserve upload directory structure |
| `uploads/gallery/.gitkeep` | Preserve gallery directory |
| `uploads/notes/.gitkeep` | Preserve notes directory |
| `README.md` | This documentation |

---

## Common Errors & Solutions

### GitHub Pages

1. **Site not loading** — Wait 2 minutes after enabling Pages. Check the branch/source setting.
2. **404 on subpages** — Ensure `.html` extension is in the URL (e.g., `/notes.html`)
3. **CSS/JS not loading** — Check file paths are relative (no leading `/`)

### PHP Hosting

1. **"config.php not found"** — Run `cp config.example.php config.php` and edit with your credentials
2. **"Database Connection Failed"** — Verify credentials in `config.php`
3. **Tables don't exist** — Import `database/schema.sql` then `database/seed.sql`
4. **Upload fails** — Create `uploads/`, `uploads/gallery/`, `uploads/notes/` directories
5. **Blank page / 500 Error** — Add `error_reporting(E_ALL); ini_set('display_errors', '1');` to `db.php`

---

## Security Notes

- `config.php` and `.env` are in `.gitignore` — never committed
- All passwords are bcrypt hashed (`password_hash()` / `password_verify()`)
- User input is escaped with `mysqli_real_escape_string()`
- File uploads restricted to PDF only, max 20MB
- `.htaccess` prevents directory listing (`Options -Indexes`)
- Session-based authentication for admin and student areas

---

## License

This project is for educational purposes. Use freely for your college/school.
