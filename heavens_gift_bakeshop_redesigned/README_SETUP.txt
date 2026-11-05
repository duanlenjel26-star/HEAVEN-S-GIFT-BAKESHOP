HEAVEN'S GIFT BAKESHOP - PROFESSIONAL REDESIGN + ONLINE ORDERING

WHAT WAS ADDED
1. Replaced dashboard/sidebar emojis with Font Awesome icons.
2. Refreshed the admin interface with a cleaner bakery theme, cards, tables, filters, buttons, and responsive layouts.
3. Added Sales Reports: Daily, Monthly, Yearly, chart, KPI summary, top products, and print button.
4. Added Online Orders in the admin panel.
5. Added a customer-facing online store under /shop/.
6. Added customer registration/login using secure password hashing.
7. Added cart, checkout, My Orders, and order status tracking.
8. Customer orders start as Pending. Admin confirmation checks stock, creates a Sales record and Sales Details, and updates product/inventory stock in one transaction.
9. Added setup_online_orders.php and database/online_orders.sql. These only ADD the three new online-ordering tables and do not recreate existing tables.

INSTALL
A. Keep your existing database: heavens_gift_bakeshop.
B. Put this folder inside C:\xampp\htdocs\.
C. Start Apache and MySQL in XAMPP.
D. Open:
   http://localhost/heavens_gift_bakeshop_redesigned/
E. Log in using your existing admin user.
F. Open:
   http://localhost/heavens_gift_bakeshop_redesigned/setup_online_orders.php
   This creates customer_accounts, orders, and order_details if they do not already exist.
G. Customer store:
   http://localhost/heavens_gift_bakeshop_redesigned/shop/

IMPORTANT
- Existing tables users, customers, products, inventory, sales, sales_details, and suppliers are preserved.
- Existing product/sales data are not deleted or recreated.
- Online order confirmation posts the confirmed order into the existing sales and sales_details tables so it appears in Sales Reports.
- The Daily Sales Report uses the existing sale_date DATE field, so it lists transactions for the selected day rather than inventing transaction times.
- Font Awesome and Chart.js are loaded from CDN, so an internet connection is needed for those visual components. The core PHP/database functions remain local.


CUSTOMER PORTAL / ADMIN SEPARATION
- Admin uses the main login at the project root.
- Customers use the separate Customer Store under /shop/.
- Customer registration is only available inside the Customer Store.
- Customer login clears any admin session in the same browser so the customer cannot access the admin pages.
- Checkout requires a complete delivery address, and the address is visible to the admin in Online Orders.

- Customers should access only /shop/ for registration, login, shopping, checkout, and order tracking.
- The main project pages are the protected admin portal and are not part of the customer interface.
