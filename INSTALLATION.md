# JEMA Water Management System - Installation Guide

## Quick Start Guide

### Prerequisites
- XAMPP (or any PHP + MySQL stack)
- PHP 7.4+
- MySQL 5.7+
- Modern web browser

---

## Step-by-Step Installation

### Step 1: Download and Extract Files

1. Download all project files
2. Extract to XAMPP htdocs folder:
   ```
   C:\xampp\htdocs\jema-water\
   ```

   Or on Linux/Mac:
   ```
   /opt/lampp/htdocs/jema-water/
   ```

### Step 2: Start XAMPP Services

**Windows:**
1. Open XAMPP Control Panel
2. Start "Apache" module
3. Start "MySQL" module

**Linux/Mac:**
```bash
sudo /opt/lampp/lampp start
```

### Step 3: Create Database

1. Open phpMyAdmin:
   ```
   http://localhost/phpmyadmin
   ```

2. Create new database:
   - Click "New" in left panel
   - Database name: `jema_water_system`
   - Collation: utf8mb4_unicode_ci
   - Click "Create"

3. Import database schema:
   - Select `jema_water_system` database
   - Click "Import" tab
   - Click "Choose File"
   - Select `database.sql` from project folder
   - Click "Import"

### Step 4: Verify Configuration

1. Open `config.php` in text editor
2. Check database settings:
   ```php
   define('DB_HOST', 'localhost');      // Usually 'localhost'
   define('DB_USER', 'root');           // MySQL username
   define('DB_PASS', '');               // MySQL password
   define('DB_NAME', 'jema_water_system');
   ```

3. Save if any changes made

### Step 5: Create Logs Directory

1. Create `/logs` folder in project root:
   ```
   jema-water/logs/
   ```

2. Ensure it's writable (permissions 755 or 777)

### Step 6: Access Application

1. Open web browser
2. Go to:
   ```
   http://localhost/jema-water/login.php
   ```

3. Login with default credentials:
   - **Username:** admin
   - **Password:** admin123

---

## Post-Installation Setup

### 1. Change Admin Password

1. After first login, go to **Profile**
2. Click **Change Password** section
3. Enter current password: `admin123`
4. Enter new secure password
5. Click **Change Password**

### 2. Configure System Settings

1. Go to **Settings** (Admin only)
2. Update company information:
   - Company Name
   - Address
   - Phone
   - Email
3. Configure billing settings:
   - Price per liter
   - Service fee
   - Tax rate
4. Set alert thresholds:
   - Low water level
   - High usage level
5. Click **Save Settings**

### 3. Add Staff Users

1. Go to **Users** (Admin only)
2. Click **Add User**
3. Fill in user details:
   - Username
   - Email
   - Full Name
   - Password
   - Role (Staff or Admin)
4. Click **Add User**

### 4. Add Water Sources

1. Go to **Water Supply**
2. Click **Add Source** button
3. Fill in source details:
   - Source Name
   - Source Type (well, tank, truck, etc.)
   - Location
   - Capacity
4. Click **Add Source**

### 5. Add First Customers

1. Go to **Customers**
2. Click **Add Customer**
3. Fill customer information:
   - Full Name
   - Phone
   - Email
   - Address
   - District/Area
   - Meter Number
4. Click **Add Customer**

---

## Troubleshooting

### Problem: "Connection refused" error

**Solution:**
1. Verify MySQL is running in XAMPP
2. Check database credentials in config.php
3. Ensure port 3306 is not blocked

### Problem: "Table doesn't exist" error

**Solution:**
1. Verify database.sql was imported successfully
2. Check database name matches config.php
3. Re-import database schema

### Problem: Login page shows blank or errors

**Solution:**
1. Clear browser cache and cookies
2. Check error logs in `/logs/error.log`
3. Verify config.php has correct database credentials
4. Ensure PHP is enabled in XAMPP

### Problem: Can't upload files or write logs

**Solution:**
1. Ensure `/logs` directory exists
2. Set folder permissions to 755 or 777
3. Check file ownership (should be web server user)

### Problem: Stylesheet not loading (unstyled page)

**Solution:**
1. Verify `assets/style.css` exists
2. Clear browser cache (Ctrl+Shift+Del)
3. Check file path is correct
4. Ensure Bootstrap CDN is accessible

### Problem: Redirect loop or session issues

**Solution:**
1. Clear browser cookies
2. Restart Apache and MySQL
3. Check session_start() is called in config.php
4. Verify PHP sessions folder has write permissions

---

## Database Backup & Restore

### Backup Database

**Using phpMyAdmin:**
1. Select `jema_water_system` database
2. Click **Export** tab
3. Select "Quick" export
4. Click **Go**
5. Save the .sql file

**Using Command Line:**
```bash
mysqldump -u root -p jema_water_system > backup_2024.sql
```

### Restore Database

**Using phpMyAdmin:**
1. Create new database
2. Click **Import** tab
3. Choose backup .sql file
4. Click **Import**

**Using Command Line:**
```bash
mysql -u root -p jema_water_system < backup_2024.sql
```

---

## Security Recommendations

### 1. Change Default Password
- ✅ Change admin password immediately after installation

### 2. Secure Database
- Create separate MySQL user (not root)
- Set strong passwords
- Backup database regularly

### 3. File Permissions
- Set appropriate file/folder permissions
- Protect sensitive files from web access

### 4. HTTPS
- Use HTTPS in production
- Implement SSL/TLS certificates

### 5. Regular Maintenance
- Monitor system logs
- Review audit log regularly
- Update software dependencies

---

## First-Time User Tasks

### Day 1: Setup
- [ ] Change admin password
- [ ] Configure system settings
- [ ] Add staff users
- [ ] Add water sources

### Day 2: Data Entry
- [ ] Add customers
- [ ] Record first water supply
- [ ] Record meter readings
- [ ] Generate test bills

### Day 3: Operations
- [ ] Test payment recording
- [ ] Generate reports
- [ ] Test alerts
- [ ] Verify all features work

---

## File Permissions (Linux/Mac)

```bash
# Navigate to project directory
cd jema-water

# Set folder permissions
chmod 755 .
chmod 755 logs
chmod 755 assets

# Set file permissions
chmod 644 *.php
chmod 644 *.sql
chmod 644 assets/*.css
```

---

## Port Configuration

### If Port 80/3306 Already in Use

**Change Apache Port:**
1. Open XAMPP Control Panel
2. Click "Config" next to Apache
3. Edit `httpd.conf`
4. Change `Listen 80` to `Listen 8080`
5. Restart Apache

**Access at:** `http://localhost:8080/jema-water/`

**Change MySQL Port:**
1. In XAMPP Control Panel, Config for MySQL
2. Edit `my.ini`
3. Change port number
4. Update config.php accordingly

---

## Performance Optimization

### For Slow Systems

1. **Disable unnecessary features:**
   - Set `display_errors = Off` in php.ini

2. **Optimize database:**
   - Run regular OPTIMIZE TABLE commands
   - Archive old audit logs

3. **Cache settings:**
   - Enable browser caching
   - Use database indexes

### For Production

1. Use dedicated hosting (not XAMPP)
2. Enable database replication
3. Implement content caching
4. Use CDN for static files
5. Monitor performance regularly

---

## Next Steps

After successful installation:

1. **Read Documentation:** Review README.md
2. **Explore Features:** Test all modules
3. **Customize Settings:** Adjust for your needs
4. **Plan Data Migration:** If migrating from old system
5. **Train Staff:** Set up user accounts and training
6. **Schedule Backups:** Set up automated backups

---

## Support Resources

- **Documentation:** README.md
- **Database Schema:** database.sql
- **PHP Errors:** /logs/error.log
- **Audit Log:** System → Audit Log table

---

## Version Information

- **Application:** JEMA Water Management System v1.0.0
- **Built:** 2024
- **PHP Required:** 7.4+
- **MySQL Required:** 5.7+
- **Browser:** Modern browsers (Chrome, Firefox, Safari, Edge)

---

## Installation Checklist

- [ ] Extract files to htdocs
- [ ] Start Apache and MySQL
- [ ] Create database
- [ ] Import database.sql
- [ ] Verify config.php settings
- [ ] Create logs folder
- [ ] Access http://localhost/jema-water/login.php
- [ ] Login with admin/admin123
- [ ] Change admin password
- [ ] Configure system settings
- [ ] Add staff users
- [ ] Add water sources
- [ ] Add test customers
- [ ] Test all features
- [ ] Create database backup

---

**Installation Complete!** 🎉

Your JEMA Water Management System is ready to use.
