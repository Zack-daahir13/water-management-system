# JEMA Water Management System

A complete web application for managing water supply, usage, billing, and reporting for Somali cities. Built with PHP, MySQL, Bootstrap, and Chart.js.

## Features

### Core Functionality
- **Dashboard Overview** - Real-time statistics on water supply, usage, revenue, and alerts
- **Customer Management** - Add, edit, and manage customer profiles and meter numbers
- **Water Supply Tracking** - Record daily water intake from various sources
- **Water Usage Tracking** - Manual meter readings and usage records
- **Billing System** - Automatic bill generation based on usage and configurable pricing
- **Payment Processing** - Record payments in multiple formats (cash, mobile money, bank transfer)
- **Reports & Analytics** - Daily, monthly, and yearly reports with statistics
- **Alerts & Issues** - System alerts for low water, high usage, unpaid bills, and leaks
- **Complaints Management** - Register and track customer complaints
- **User Management** - Admin and staff role-based access control
- **System Settings** - Configure pricing, fees, alerts, and company information

### Design Features
- Clean, modern responsive UI
- Blue/white color scheme with professional styling
- Mobile-friendly interface
- Sidebar navigation
- Dashboard with statistics cards
- Interactive charts using Chart.js
- Modal forms for data entry
- Pagination for large datasets

## System Requirements

- **PHP 7.4+**
- **MySQL 5.7+**
- **Apache with .htaccess support (optional)**
- **Modern web browser**

## Installation

### Step 1: Setup Directory
1. Download and extract files to your XAMPP htdocs:
   ```
   C:\xampp\htdocs\jema-water
   ```

### Step 2: Create Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `jema_water_system`
3. Import the `database.sql` file:
   - Click Import
   - Select database.sql
   - Click Go

### Step 3: Configure Application
1. Open `config.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'jema_water_system');
   ```

### Step 4: Access Application
1. Open your browser
2. Navigate to: `http://localhost/jema-water/login.php`
3. Use default credentials:
   - **Username:** admin
   - **Password:** admin123

## File Structure

```
jema-water/
├── index.html                 # Placeholder
├── login.php                  # Login page
├── dashboard.php              # Main dashboard
├── customers.php              # Customer management
├── water-supply.php           # Water supply tracking
├── usage.php                  # Water usage tracking
├── billing.php                # Billing management
├── payments.php               # Payment processing
├── reports.php                # Reports and analytics
├── alerts.php                 # Alerts management
├── complaints.php             # Complaints management
├── users.php                  # User management (Admin only)
├── settings.php               # System settings (Admin only)
├── profile.php                # User profile
├── logout.php                 # Logout handler
├── config.php                 # Database configuration
├── auth.php                   # Authentication class
├── database.sql               # Database schema
├── assets/
│   └── style.css              # Main stylesheet
├── includes/
│   └── sidebar.php            # Sidebar navigation
└── api/
    ├── get-customer.php       # Get customer data
    └── get-user.php           # Get user data
```

## User Roles

### Admin
- Full access to all features
- User management
- System settings
- Reports and analytics

### Staff
- Dashboard view
- Can record water usage and payments
- Can view reports (read-only)
- Limited to assigned tasks

## Default Admin Account

```
Username: admin
Password: admin123
```

**IMPORTANT:** Change this password immediately after first login.

## Database Tables

1. **users** - Admin and staff accounts
2. **customers** - Customer records and meter information
3. **water_sources** - Water supply sources (well, tank, truck, etc.)
4. **water_supply** - Daily water intake records
5. **water_usage** - Customer meter readings
6. **bills** - Customer billing records
7. **payments** - Payment transactions
8. **alerts** - System and operational alerts
9. **complaints** - Customer complaints and issues
10. **settings** - System configuration
11. **audit_log** - Action history and logs

## Configuration Options

### Settings (Admin Panel)

- **Price per Liter** - Unit price for billing
- **Monthly Service Fee** - Fixed monthly charge
- **Tax Rate** - Percentage tax on bills
- **Low Water Alert Level** - Threshold for low water warnings
- **High Usage Alert Level** - Threshold for high usage alerts
- **Company Information** - Name, address, phone, email

## Security Features

- Password hashing with BCrypt
- Session-based authentication
- Role-based access control
- SQL injection prevention with prepared statements
- XSS protection
- CSRF security headers
- Audit logging of all actions

## Key Features Details

### Dashboard
- Real-time statistics
- Revenue tracking
- Water supply/usage charts
- Recent transactions
- Active alerts

### Customer Management
- Add new customers with meter numbers
- Track customer details and addresses
- Manage customer status (active/inactive/suspended)
- Search and filter customers

### Water Supply
- Record daily water intake
- Multiple source types supported
- Cost tracking per supply
- Low water level alerts

### Water Usage
- Manual meter reading entry
- Automatic usage calculation
- Historical tracking
- Estimated billing based on usage

### Billing
- Auto-generate bills for billing periods
- Configurable pricing per liter
- Service fees and taxes
- Multiple payment status tracking

### Payments
- Record payments in multiple methods
- Automatic bill status updates
- Transaction reference tracking
- Payment history

### Reports
- Daily, monthly, and yearly reports
- Revenue analytics
- Water supply/usage statistics
- Export capabilities

### Alerts
- System alerts for operational issues
- Severity levels (low, medium, high, critical)
- Alert assignment and tracking
- Status management

### User Management
- Create staff accounts
- Assign roles (admin/staff)
- Manage user status
- Password reset functionality

## Common Tasks

### Adding a Customer
1. Go to Customers menu
2. Click "Add Customer" button
3. Fill in customer details
4. Assign meter number
5. Click "Add Customer"

### Recording Water Supply
1. Go to Water Supply menu
2. Select date and source
3. Enter quantity and cost
4. Click "Record"

### Recording Meter Reading
1. Go to Water Usage menu
2. Select customer and date
3. Enter current meter reading
4. System calculates usage automatically
5. Click "Record"

### Generating Bills
1. Go to Billing menu
2. Set billing period (start/end dates)
3. Set unit price
4. Click "Generate"
5. Bills are auto-created for all customers

### Recording Payment
1. Go to Payments menu
2. Select bill from dropdown
3. Enter amount paid
4. Select payment method
5. Click "Record"

## Troubleshooting

### Connection Error
- Verify MySQL is running
- Check database credentials in config.php
- Ensure database.sql was imported

### Login Issues
- Clear browser cache and cookies
- Check if user account is active
- Verify password (default: admin123)

### Permission Denied
- Log in with admin account to access admin features
- Staff accounts have limited access

### Styling Issues
- Clear browser cache
- Check if assets/style.css is in correct location
- Ensure Bootstrap CDN is accessible

## Optimization Tips

### For Low Internet Speed
- System is optimized for low bandwidth
- Minimize use of large reports
- Use pagination instead of loading all records
- Cache commonly used data

### Database Performance
- Regular database backups
- Clean old audit logs periodically
- Use indexes on frequently searched fields

## Maintenance

### Regular Tasks
1. Monitor system alerts
2. Review pending payments
3. Generate monthly reports
4. Update system settings as needed
5. Backup database weekly

### Database Backup
```sql
mysqldump -u root jema_water_system > backup.sql
```

### Database Restore
```sql
mysql -u root jema_water_system < backup.sql
```

## Support & Updates

For issues, feature requests, or updates:
- Check system logs in /logs/error.log
- Review audit log for action history
- Contact system administrator

## License

JEMA Water Management System © 2024

---

**Version:** 1.0.0
**Last Updated:** 2024
