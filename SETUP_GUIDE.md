# Setup Guide

詳細全設定流程：https://hackmd.io/@Kaiserouo/H1ybQffvc，或是如果連結炸了，你可以看備份`FR_PROCESS.md`。不過因為有一些hackmd的語法所以在github上看起來可能會怪怪的，沒事的話請無視`:::spoiler ... :::`的部分，因為前半段是failed attempt的紀錄。

這個project基本上就是在lab2上加東西。**如果未來他們把lab2更新了，請去生他們2022年前的投影片出來或是靠下面的資訊和上面的流程通靈。**

## AP
參照lab 2。如果未來不再給AP而是給無線網卡的話，請自行通靈。

詳細：
+ DLINK DIR-615
  + 助教那邊很多，大概快要十年了。
  + 他們用dd-wrt介面，說不定其他AP也可以。
+ Chillispot
  + 未來被CoovaChili之類的東西取代了，可能有差異，不確定。
  + 完整設定的話，去Service -> Hostspot -> Chillispot：
    + Chillispot: Enable
    + Separate Wifi from the LAN Bridge: Disable
    + Primary / Backup Radius Server IP/DNS: 都是192.168.1.2
    + DNS IP: 8.8.8.8
    + Redirect URL: https://192.168.1.2/login.php (ref. 下面)
    + Shared Key: testing123 (ref. 下面)
    + Radius NAS ID: ta (不知道是甚麼，我們也沒設定到，反正寫了好像沒差)
    + UAM Secret: cnl2022_team3 (可以自己設定，跟網頁寫的一樣就好)
+ Lab2有另一個不用AP的方法是用無線網卡加CoovaChili。我不會，請通靈。
+ 記得**一定要連外網！否則redirect會不成功。**

## `192.168.1.2`: FreeRadius, SQL, Web

基本上就是跑lab2一次。然後未來開`192.168.1.2`的VM的時候要把標題三個服務開起來就是了。或是如果未來lab2更新了，用詳細全設定流程跑/通靈。

大致的參數和版本如下：
+ Ubuntu 16.04.7
  + 事實上lab2也是用這個版本，**不要做死用更新的版本**，不然Freeradius 3和MySQL 8很難搞定。我花了6小時最後炸了，想看爆炸流程請看詳細全設定流程的Failed attempt部分。
  + 下面載的時候就是那些版本了，ubuntu他們應該沒有再更新16.04的apt了。
+ MySQL ver 14.14 distrib 5.7.33
  + 反正照lab2跑不會有問題，FYI用。
  + 密碼設定如下，記得**如果你更動任何密碼或secret之類的東西的話，記得去看看網頁、`/etc/freeradius/*`、QRCode web server有沒有任何需要用到這些的，要改掉**。
    + radius@localhost: radpass
    + root@localhost: 123456
    + phpmyadmin@localhost: 123456
      + 最後其實沒什麼用到，反正grant privileges可以透過mysql做
+ FreeRadius 2.2.8
  + 設定方面參照lab2和[cnl2018](https://github.com/hortune/cnl2018/tree/master/lab2)。我當時是用cnl2018的網頁先測試過freeradius可以使用才真的放我們的網頁去`/var/www/html`的。
  + Secret，記得如果改了要把其他出現這些東西的地方全改掉：
    + shared secret: testing123
    + UAM secret: cnl2022_team3

要注意的幾個地方：
+ FreeRadius的`sqlcounter`和`dictionary`記得設定，雖然有可能用不到...。
+ 我們使用FreeRadius內建的`expiration` module，記得開起來。在全設定流程最後應該有參考過程與連結。
+ 所有登入網頁相關的東西都要放進去`/var/www/html`，詳細如下。事實上你也可以把整個repository的所有東西丟進`/var/www/html`，最保險。
```
cp -r html_comp lib *.php *.js /var/www/html
```
+ AP的Redirection設定為`https://192.168.1.2/login.php`。注意如果在設定期間有任何SSL相關錯誤請務必解決，因為NFC和camera必須使用HTTPS，所以請至少讓HTTPS網頁可以正常讀的到。
  + 不過FreeRadius的SSL相關事項我全部砍掉了，ref. 全設定流程。事實上我們用HTTPS只是為了開NFC和camera。
  + 注意目前似乎只有chrome支援NFC和camera，如果你不知道為甚麼沒有被redirect到chrome而是用其他瀏覽器開的話可能無法使用NFC和camera。
    + RIP iOS。我們demo用android測的。
+ 因為QRCode server是別台機器，所以必須開root帳號給外部。ref. 全設定流程的最後面或下面的QRCode顯示器。

## QRCode 顯示器
跑`cd qrcode; python server.py`，然後去`http://127.0.0.1:8888`之類的就可以看的到了。

注意事項：
+ python 3.8以上最好，我們有用f-string。requirement在`qrcode/requirement.txt`。
+ 記得去`qrcode/const.py`設定一些參數。如果有不知道的設定...那去code裡面通靈。
+ 因為這是不同的機器 (分工/設計考量，加上當時VM上跑python炸了)，所以記得要在`192.168.1.2`的SQL server弄一個可以給非localhost的帳號。設定細節還是去參考全設定流程最好，或是你可以去查一下怎麼把SQL給非localhost用。
+ 他當然要放在AP底下，你可以選擇用static IP `192.168.1.0/24`。我們是單純登入然後用預先在freeradius加的帳號登入，所以我們demo時這台的IP其實是在`192.168.182.0/24`。

## 大致設定流程
反正就是：
+ 弄好192.168.1.2的VM
+ 弄好QRCode顯示器
+ 都連到連外網的AP
+ run QRCode server
+ 然後接下來有人連去AP之後就可以像demo登入了