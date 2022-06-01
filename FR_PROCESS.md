# FreeRadius setup

:::spoiler Failed attempt using Ubuntu 20.04 and Freeradius 3 and Chillispot (i.e. AP from TA).

## Some information
+ Ubuntu: 20.04.3
    + name, password: `team3`
+ MySQL:
    + `'root'@'localhost'` password: `123456`
    + `'radius'@'localhost'` password: `123456`

## Process
絕大多數從 https://www.gushiciku.cn/dl/0lGNh/zh-tw 來。

用`ubuntu-20.04.3-desktop-amd64.iso`，disk 30GB，minimal installation，不自己切硬碟，name & passwd: `team3`，

然後備份

```bash!
# do some update
sudo apt update
sudo apt -y upgrade    # that is gonna take some time

# install web server & php
sudo apt‐get -y install apache2
sudo apt‐get -y install php libapache2‐mod‐php php‐gd php‐common php‐mail php‐mail‐mime php‐mysql php‐pear php‐db php‐mbstring php‐xml php‐curl

# check php version, should be 7.4.3, at least for me
php ‐v

# restart apache
sudo systemctl restart apache2

# install mysql
sudo apt -y install mysql‐server
```
<!-- ![](https://i.imgur.com/5OkztMB.png) -->

```bash!
# give 'root'@'localhost' a password
# note that `sudo mysql_secure_installation` won't work
# (ref. https://stackoverflow.com/questions/41645309/mysql-error-access-denied-for-user-rootlocalhost)
sudo mysql
> ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '123456';
> FLUSH PRIVILEGES;
> EXIT;
```

SQL root password: `123456`
未來的`mysql -uroot -p123456`都可以省略最後密碼的部分，這樣的話就是進去後再打。

```bash
mysql -u root -p 123456
# type your password
> CREATE DATABASE radius;

# this will create an account, "123456" is radius' password
# note we use mysql 8.0, cannot create user implicitly by GRANT.
# (ref. https://stackoverflow.com/questions/50177216/how-to-grant-all-privileges-to-root-user-in-mysql-8-0)
# (note: I don't know if `WITH GRANT OPTION` is necessary...)
> CREATE USER 'radius'@'localhost' IDENTIFIED BY '123456'
> GRANT ALL PRIVILEGES ON radius.* TO 'radius'@'localhost' WITH GRANT OPTION;
> FLUSH PRIVILEGES;
> EXIT;
```

mysql, radius@localhost's password: `123456`

```bash!
# install freeradius
sudo apt‐get install freeradius freeradius‐mysql freeradius‐utils

# should be 3.0.20
freeradius -v

# should see `Ready to process requests`
# if any port is in use, find pid by `sudo netstat -tulp | grep "18120" (or other port) and SIGKILL it.
# I don't know if `freeradius -v` caused it, though...
# (ref. https://stackoverflow.com/questions/51335723/failed-binding-to-auth-address-127-0-0-1-port-18120-bound-to-server-inner-tunnel)
# Or just `systemctl stop freeradius`, oh well
sudo systemctl stop freeradius
sudo freeradius ‐X
# if did see `Ready to process requests`, do Ctrl+C

# load table structure & column definition
sudo mysql ‐u root ‐p123456 radius < /etc/freeradius/3.0/mods‐config/sql/main/mysql/schema.sql

# see if tables are really loaded
mysql ‐u root ‐p ‐e "use radius; show tables;"
```

```bash!
# follows the website & pdf
sudo ln ‐s /etc/freeradius/3.0/mods‐available/sql /etc/freeradius/3.0/mods‐enabled/
sudo vim /etc/freeradius/3.0/mods‐enabled/sql
```

看到`sql {`後 (應該挺前面的)，做以下更改
+ `dialect = "mysql"`
+ `driver = "rlm_sql_${dialect}"`
+ 把`mysql {}`底下的`tls {}`全部comment掉
+ `Connection info`有4行給uncomment，改掉`password`成SQL `'radius'@'localhost'`的password。

```bash!
# change link owner
sudo chown ‐R freerad:freerad /etc/freeradius/3.0/mods‐enabled/sql
sudo chgrp -h freerad /etc/freeradius/3.0/mods‐enabled/sql

# check if freeradius is normal
sudo systemctl stop freeradius
sudo freeradius -X
# if `Ready to process requests`, then everything's OK, Ctrl+C
sudo systemctl stop freeradius
sudo systemctl start freeradius
```

```bash!
# test by radtest
# add an user
mysql -uroot -p123456
> use radius;
> insert into radcheck username,attribute,op,value ) values ("test", "Cleartext-Password", ":=", "testpwd");
> exit;

radtest test testpwd localhost 0 testing123
# should show Access-Accept
```

其中，`radtest`最後的是shared secret / client secret，位在`/etc/freeradius/3.0/clients.conf`的`client localhost { secret = testing123 }`。你可以改，但是AP上面寫的的Shared key要跟這個一樣。

至於UAM Secret似乎是在網站和AP中間要的，自己決定。

之後發現也許要改掉clients.conf: 要加`client 192.168.1.0/24 {secret = testing123}`。

mods enqbled default: authorize chap comment? no it's no use
目前卡在chap沒辦法login，他說密碼錯誤。
> 我目前猜可能把[對應的這一行改成這樣](https://github.com/sycLin/CNLab-Lab2/blob/06d90165f6e18ab62a1a95ff56a11c5566a52736/hotspotlogin.php#L74)就好，我不知道。
> 機率不大

in sites-available
+ gedit default (uncomment sql under authorize {} & accounting{} ) (我是把`-`去掉)
+ gedit inner tunnel (uncomment sql under authorize {} )

---

note: 
+ 試試看各種生chap方法
    + 可能要無視uamsecret? 我不知道，試試看
    + 根據[這裡](https://stackoverflow.com/questions/37377229/how-to-make-freeradius-authenticate-with-chap-instead-with-pap)的做法也可以，尤其是那個`update control`。
+ 話說登入頁面叫captive portal，FYI，可以找找看
+ [radtest.in](https://github.com/FreeRADIUS/freeradius-server/blob/master/src/bin/radtest.in), [radclient.c](https://github.com/FreeRADIUS/freeradius-server/blob/master/src/bin/radclient.c), [fr_radius_encode_chap_password](https://github.com/FreeRADIUS/freeradius-server/blob/12d3fdfc4293b1b6c4513146d138e913eec91ac0/src/protocols/radius/encode.c#L52)
+ 看看要不要用看看那個daloradius…有成果比沒有好
+ [php的範例code](https://www.php.net/manual/en/function.radius-put-attr.php)感覺有料...? 或是[這邊](https://www.php.net/manual/es/radius.constants.attributes.php)也有一點範例，都沒用到uamsecret。

ref:
+ [前人code/我們的repo](https://github.com/AndyDu01/CNL2022/blob/main/website/index.php)，[更前人code](https://github.com/sycLin/CNLab-Lab2/blob/master/hotspotlogin.php?fbclid=IwAR2cgxJ_TWZY_x8wMCfL3M-ua8FwIkSTUIea-l98Pn8SO268pgwnqORq9DE)
+ [freeradius-server](https://github.com/FreeRADIUS/freeradius-server/tree/master/src)

now try:
+ auth type改local ([ref](https://wiki.freeradius.org/guide/faq#common-problems-and-their-solutions_pap-authentication-works-but-chap-fails), [ref?](https://freeradius-users.freeradius.narkive.com/IQ0Fk4sD/auth-login-incorrect-user-chap-password))
+ chap運算方式改變 (上面note的3個連結)
+ 看看nas到底是甚麼
+ 用daloradius

現在發現在AP上面的是chillispot，後來已經被defunct，被coovachili取代了。第二份投影片的確是用freeradius 3 + coovachili，所以目前在思考降版本的事情...

:::

---

先說，看起來似乎只要用Ubuntu 16.04一切問題都可以解決...。
不要做死用更新的版本，拜託。

隨便去淡江大學之類的FTP server都找的到16.04的iso檔，ubuntu官網太慢了。

+ Ubuntu 16.04.7
    + name, password: `team3`
+ MySQL ver 14.14 distrib 5.7.33
    + radius@localhost: radpass
    + root@localhost: 123456
    + phpmyadmin@localhost: 123456
+ FreeRadius 2.2.8
    + shared secret: testing123
    + UAM secret: cnl2022_team3

```bash
sudo apt-get -y install vim
sudo apt-get -y install tasksel
sudo apt-get update
sudo tasksel install lamp-server

mysql --version
# Ver 14.14 Distrib 5.7.33
```

SQL root password: `123456`

```
sudo apt-get -y install phpmyadmin
```
![](https://i.imgur.com/icYQbo0.png)
MySQL applicaiton password for phpmyadmin: `123456`

```bash
sudo cp /etc/phpmyadmin/apache.conf /etc/apache2/conf.d
sudo vim /etc/apache2/apache2.conf
# add at last line: `Include /etc/phpmyadmin/apache.conf`
sudo /etc/init.d/apache2 restart
```

然後就可以去`http://localhost/phpmyadmin`了：
+ name: `phpmyadmin`
+ passwd: `123456`

不過基本上應該還是用`root / 123456`進去啦，他只要打mysql的username & password就都可以進去。
注意如果要管理帳戶之類的還是用`root`比較快。

```bash
sudo a2enmod ssl
sudo a2ensite default-ssl
sudo /etc/init.d/apache2 restart
# yes there will be no problem at all...

sudo apt-get -y install freeradius
freeradius -v
# freeradius version: 2.2.8

sudo apt-get -y install freeradius-mysql
sudo /etc/init.d/freeradius restart

sudo vim /etc/hosts
# comment out the line `::1 localhost ip6 localhost ip6 loopback` (or something like that)

mysql -uroot -p123456
> create database radius;
> exit;

sudo -s
cd /etc/freeradius/sql/mysql
mysql -uroot -p123456 radius < ippool.sql
mysql -uroot -p123456 radius < schema.sql
mysql -uroot -p123456 radius < nas.sql
mysql -uroot -p123456 radius < admin.sql
```
然後用`root / 123456`進去phpmyadmin，找到上面的User management之類的，找到user `radius`，進去把他的privileges全打勾，然後按確認。

SQL radius@localhost password: **`radpass`!** **有改掉! 跟上面不一樣!**

```bash
sudo -s
vim /etc/freeradius/sites-enabled/default
# uncomment `sql` under authorize{}, account{}, session{}, post-auth{}

vim /etc/freeradius/radiusd.conf
# 1. fix `port = 0` to `port = 1812` on first listen{} (you will see example writes 1812), and the next listen{} write `port = 1813`
#    (i.e. listen port 1812, accounting (& listen) port 1813)
# 2. under modules{}, uncomment `$INCLUDE sql.conf`
# 3. fixed the following log settings value to the correct value
#     stripped_names = yes
#     auth = yes
#     auth_badpass = yes

vim /etc/freeradius/sql.conf
# under sql{}, fix password to the password of radius@localhost

vim /etc/freeradius/clients.conf
# add:
# client 192.168.1.0/24 {
#     secret = testing123
# }
```

```
mysql -uradius -pradpass
> use radius;

> insert into radgroupreply (groupname, attribute, op, value) values ('user', 'Auth-Type', ':=', 'CHAP');
> insert into radgroupreply (groupname, attribute, op, value) values ('user', 'Service-Type', ':=', 'Framed-User');

> insert into radcheck (username, attribute, op, value) values ('ta', 'Cleartext-Password', ':=', 'tatest');
> insert into radusergroup (username, groupname) values ('ta', 'user');

> exit;

sudo /etc/init.d/freeradius restart
```

然後去`radtest ta tatest localhost 1 testing123`應該會accept。

我目前想用[這個](https://github.com/hortune/cnl2018/tree/master/lab2)測試。
反正不管怎樣把這個repo給放到VM裡。我接下來的路徑都是假設放在`/shared/cnl2018`裡面，你可以自己改。

```
cp -r /shared/cnl2018/lab2/website/* /var/www/html
```

然後在`/etc/freeradius/dictionary`最後加兩行 (用tab分隔)：
```
ATTRIBUTE	Max-Hourly-Traffic	3003	integer
ATTRIBUTE	Max-Hourly-Session	3004	integer
```

(事實上我猜其實可以`cp -r /shared/cnl2018/lab2/freeradius/* /etc/freeradius`之類的...說不定他們都改好了...隨便啦，他們也沒有給readme)

然後把ip address改掉，用`ifupdown`：動`/etc/network/interface`加上
```
iface enp0s3 inet static
    address 192.168.1.2
    netmask 255.255.255.0
    gateway 192.168.1.1
```

然後`ifdown / ifup`一下過後，確認`ip a`裡面的`enp0s3`只有一個ip address (如果有`192.168.182.*`開頭之類的就重新`ifdown, ifup`一遍)

然後就是弄一下AP (照助教的pdf上面的做，Radius NAS ID好像沒差...?)，然後網站的資料改一下 (`radius/`資料夾裡面的一些登入資訊要改，記得不要眼花把uamsecret打錯...我卡了6小時在這裡)，然後就好了。注意redirect是要設去`http(s)://192.168.1.2/login.php`。

+ note to teammates:
    + SQL radius@localhost password: `radpass`


---

把sql counter弄好：
+ 把cnl2018那個repo的freeradius/sql/mysql/counter.conf給弄過去
+ 把一些sqlcounter{}加進去/etc/freeradius/sites-enabled/default的authorize{}進去
    + 看你們要不要加noresetcounter，當作max all traffic
    + 這單純是弄traffic和session time而已，不過需要在某個點expire的話那需要把expiration弄好

[把expiration弄好](https://networkradius.com/doc/3.0.10/raddb/mods-available/expiration.html)：
+ 在/etc/freeradius/sites-enabled/default的authorize{}的最後面加expiration
+ 在/etc/freeradius/radiusd.conf的instantiate{}加expiration
    + 之後就可以在radcheck加`Expiration := 25 May 2022 12:12:12`之類的東西了

然後我們要[從別台(非localhost)登入mysql](https://stackoverflow.com/questions/14779104/mysql-how-to-allow-remote-connection-to-mysql?fbclid=IwAR3J3T1IPm4V7DZym86iwdwqomvJI55Zd2Jh0ZYW7nVbGVRcZ3gT16Z5dmE)，需要：
```
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '123456' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```
就可以不限定IP了，否則從非localhost的地方想登root沒辦法登入sql。
然後也要在`/etc/mysql/mysql.conf.d/mysqld.cnf`裡面comment掉`bind-address = 127.0.0.1`，否則好像也沒辦法。

然後如果https的東西有connection timeout或之類的，[可以試試看](https://www.ztabox.com/knowledgebase_article.php?id=175):
```
a2enmod rewrite
a2enmod ssl
systemctl restart apache2
```
