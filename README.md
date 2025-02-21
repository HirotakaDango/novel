# Novel

A simple and lightweight novel management system.

## Features
- Manage and organize novels easily
- User-friendly interface
- Lightweight and fast

## Installation
1. Clone the repository:  
   ```sh
   git clone https://github.com/HirotakaDango/novel.git
   ```
2. Navigate to the project folder:  
   ```sh
   cd novel
   ```
3. Deploy on a local or online server with PHP support.

## Activating SQLite Database
1. Ensure your server has SQLite enabled in PHP. You can check by running:  
   ```sh
   php -m | grep sqlite
   ```
2. If SQLite is not enabled, enable it in `php.ini` by uncommenting:  
   ```
   extension=sqlite3
   ```
3. Create the database file:  
   ```sh
   touch database.sqlite
   ```
4. Set the correct permissions:  
   ```sh
   chmod 777 database.sqlite
   ```
5. Your SQLite database is now ready!

## Usage
- Open `index.php` in your browser.
- Start managing your novels.
