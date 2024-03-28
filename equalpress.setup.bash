# Function to print colored text
print_color() {
    local color="$1"
    local text="$2"
    case "$color" in
        "black") echo -e "\033[1;30m$text\033[0m" ;;
        "red") echo -e "\033[1;31m$text\033[0m" ;;
        "green") echo -e "\033[1;32m$text\033[0m" ;;
        "yellow") echo -e "\033[1;33m$text\033[0m" ;;
        "blue") echo -e "\033[1;34m$text\033[0m" ;;
        "magenta") echo -e "\033[1;35m$text\033[0m" ;;
        "cyan") echo -e "\033[1;36m$text\033[0m" ;;
        "white") echo -e "\033[1;37m$text\033[0m" ;;
        *) echo "Invalid color" >&2 ;;
    esac
}

# shellcheck disable=SC2164
cd /home/"$USERNAME"/www

# Replace the .htaccess file
print_color "yellow" "Downloading and replacing the .htaccess file..."
docker exec -ti "$USERNAME" bash -c "
rm public/.htaccess
wget https://raw.githubusercontent.com/yesbabylon/b2/master/eQualPress_template/public/.htaccess -O public/.htaccess
"

print_color "yellow" "Renaming public/index.php to public/equal.php to avoid conflicts with WordPress..."
docker exec -ti "$USERNAME" bash -c "
mv public/index.php public/equal.php
"

# shellcheck disable=SC2010
EQ_PORT=$(( 80 - 1 + $(ls -l /home | grep -c ^d) ))

print_color "green" "Downloading, installing and setting up WordPress"
# 1. Download WP-CLI
# 2. Make the downloaded WP-CLI executable
# 3. Create a directory for local binaries if it doesn't exist
# 4. Move the downloaded WP-CLI to the local binaries directory
# 5. Download WordPress core files
# 6. Create a wp-config.php file
# 7. Create uploads directory
# 8. Install WordPress
# 9. Change the owner of the files to www-data
docker exec -ti "$USERNAME" bash -c "
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mkdir -p /usr/local/bin
mv wp-cli.phar /usr/local/bin/wp
wp core download --path='public/' --locale='en_US' --version=$WP_VERSION --allow-root
wp config create --path='public/' --dbname=$DB_NAME --dbuser=$APP_USERNAME --dbpass=$APP_PASSWORD --dbhost=$DB_HOSTNAME --allow-root
mkdir -p public/wp-content/uploads
wp core install --path='public/' --url=$USERNAME:$EQ_PORT --title=$WP_TITLE --admin_user=$APP_USERNAME --admin_password=$APP_PASSWORD --admin_email=$WP_EMAIL --skip-email --allow-root
chown -R www-data:www-data .
"
