# CineMatch — PHP + MySQL Movie Recommendation System


## Project title

**CineMatch — Movie Recommendation System**

The visible project title is CineMatch. The technical folder path and MySQL database name remain `movieverse` so existing XAMPP installations continue to work without changing URLs or database configuration.

## Fast XAMPP setup

1. Copy the complete `movieverse` folder into:
   `C:\xampp\htdocs\movieverse`
2. Open XAMPP Control Panel.
3. Start **Apache** and **MySQL**.
4. Open:
   `http://localhost/movieverse/install.php`
5. Click **Install database and 250 movies**.
6. Open:
   `http://localhost/movieverse/`

## Database settings

The project uses the standard XAMPP defaults:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `movieverse`
- User: `root`
- Password: empty

Edit `config/database.php` and `install.php` only if your MySQL credentials differ.

## Features

- Exactly 250 movies imported by the installer
- Real movie poster URLs with local fallback poster
- Search by title, cast, director, or genre
- Genre, decade, rating, and sorting filters
- Mood-based random recommendations
- Movie details pages
- IMDb links
- Browser-based favourites/My List
- Responsive mobile UI
- PDO prepared statements
- Helpful setup/error states

## Troubleshooting

### “Database is not ready”
Open `http://localhost/movieverse/install.php` and install once.

### Installer cannot download data
Enable `extension=curl` in `C:\xampp\php\php.ini`, restart Apache, and retry. Also confirm your internet connection.

### MySQL access denied
Check your root password and update it in both `config/database.php` and `install.php`.

### Posters do not load
The UI automatically switches to `assets/img/fallback-poster.svg`. External poster hosts can occasionally block or rate-limit requests.
