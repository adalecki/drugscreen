#!/bin/bash
U="$USER"
echo "Enter database password"
read MARIADB_PW
echo "$MARIADB_PW"
apt-get -y update
apt-get -y upgrade
echo mariadb-server mysql-server/root_password password $MARIADB_PW | sudo debconf-set-selections
echo mariadb-server mysql-server/root_password_again password $MARIADB_PW | sudo debconf-set-selections
apt-get -y install mariadb-server openssh-server python-software-properties openssh-server nginx php5-fpm php5-gd php-apc php5-mcrypt php5-cli php5-curl php5-mysql openbabel subversion
wget https://raw.githubusercontent.com/adalecki/drugscreen/master/www
mv www /etc/nginx/sites-available
rm /etc/nginx/sites-enabled/default
ln -s /etc/nginx/sites-available/www /etc/nginx/sites-enabled/www
svn export https://github.com/adalecki/drugscreen/trunk/publication_database /usr/share/nginx/html/publication_database
wget https://github.com/adalecki/drugscreen/raw/master/sqlbuddy.tar.gz
gunzip sqlbuddy.tar.gz
tar -zxf sqlbuddy.tar -C /usr/share/nginx/html/
echo "AllowUsers $U" >> /etc/ssh/sshd_config
echo "		upstream php5-fpm-sock {
			server unix:/var/run/php5-fpm.sock;
		}" > /etc/nginx/conf.d/php-sock.conf
echo "CREATE DATABASE publication_database" | mysql -u root -p$MARIADB_PW
echo "CREATE USER 'publicationdb'@'localhost' IDENTIFIED BY 'publicationdb';" | mysql -u root -p$MARIADB_PW
echo "GRANT ALL ON publication_database.* TO 'publicationdb'@'localhost';" | mysql -u root -p$MARIADB_PW
mysql -u root -p$MARIADB_PW publication_database < /usr/share/nginx/html/publication_database/publication_database.sql
chown -R www-data:www-data /usr/share/nginx/html/publication_database/svgimages
chown -R www-data:www-data /usr/share/nginx/html/publication_database/temp
/etc/init.d/php5-fpm restart
/etc/init.d/nginx reload
