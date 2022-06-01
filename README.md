# CNL Final 結報
- [Repo](https://github.com/AndyDu01/CNL2022)
- [Slides](https://docs.google.com/presentation/d/1hOCDbEadS4CPkbE-IiQuwf-A5U5fPKY1s44ugrScy34/edit?usp=sharing)
## 專題動機
我們從日常出發，發現公用網路的不便之處，結合課堂所學的知識，改善登入公共網路的流程。
一般的公用網路情境下:
- 訪客Admin索取隨機生成的帳密，不方便輸入，我們希望能透過 NFC 和 QR code 來免去輸入帳號密碼流程。
- 同時為了避免訪客因為過去曾經使用過網路，在Admin不知情的情況下登入，利用Radius處理帳號權限。
- 一旦經過註冊，內部人士可以直接使用自身的證件進行登入，免除額外的硬體設備。
- 以上的解決方案並不侷限在，在其他地方的網路（e.g. 咖啡廳）也存在應用的情境。
## 專題介紹
我們在這次的專題中實做了兩種登入網路的方式，分別是 NFC 及 QR code。

| | 內部人員 | 外部人員 |
| ---- | ---| -----|
| 舉例 | 學生、員工  | 校外人士、顧客 |
| 目標 | 1. 有更簡易及方便的登入方式。</br>2. 不須記誦密碼。 |1. 有更簡易及方便的登入方式。</br>2. 希望只有今天來訪的人能使用網路。 |
| 使用技術 | NFC | QR code |
| 設計 | 1.資料庫內存有所有內部人員的資料。</br>2. 使用手機利用 NFC 掃描悠遊卡即可連上網路。 | 1. 掃 QR code 後便能使用網路。</br>2. 新增一組帳號密碼給這名使用者，並在隔日刪除這組帳號密碼。</br>3. 使用 FreeRADIUS 進行計量服務，限制使用者的額度。 |

## 網路架構
主要網路節點
+ AP: 有Coovachili負責freeradius登錄部分
+ Freeradius / SQL / Web server
+ QRCode顯示器

![](https://i.imgur.com/BGAKigL.png)

---

![](https://i.imgur.com/3FcpEAw.png)

## 實驗詳細步驟
### Free Radius & SQL
+ FreeRADIUS：做身分驗證與帳號登入。
    + 使用expiration模組，在午夜時自動登出帳號。
+ SQL：作為 FreeRADIUS 後端，可以方便地自動化更改帳號密碼等。
    + 讓QRCode顯示器可以註冊guest account以及確認目前顯示出的QRCode對應的guest account是否已經登入。
    + 午夜時會刪除今天辦理的guest account。
<!-- ![](https://i.imgur.com/S3JuWkZ.png) -->

### 登入網頁
**程式語言使用 PHP去動態生成網頁，HTML、JS、CSS進行使用者互動與美化**

||QR code|NFC|
-|-|-
|API|html5-qrcode|NDEFReader|
|實作|自動檢查相機權限及可使用的相機種類。</br>會優先選擇後鏡頭。|使用MD5處理讀取的資訊，確保符合SQL內部設定|
- 掃描完成後會自動送出(重複點擊可以取消操作)。
- 考量到並非所有裝置均支援NFC以及相機，將自動偵測使用者的配置動態調整畫面。
- 為了避免網頁卡頓，在檢查鏡頭時使用Async機制，但會影響畫面呈現(例如出現無法使用的QRcode按鈕)，最後改以嵌入預設動畫去提升使用者體驗。


### QR Code Server
QR code server 為 QR code 顯示器後面的 web server。我們以 Python 實作。QR code server 的工作有以下五項，這裏一一說明實作方法。
1. 生成隨機的帳號訪客用帳號密碼
    - 如果目前顯示在畫面的帳號密碼已被使用，或已過期，server 會利用 Python 函數隨機生成 8 個字元的帳號密碼。
    - 判斷帳號是否被使用的方法為檢查 radius database 的 radacct table 是否有該帳號。
    - 判斷帳號是否過期的方法為檢查 radius database 的 radcheck table，該帳號 Expiration 的時間。
    - 使用 SQL 的方法會在下一點做說明。
2. 將帳號密碼放入 SQL
    - 使用 Python 的 mysql.connector module 連線到 freeRADIUS 的 MySQL database 以及執行指令。
    - 直接在 radcheck table 中插入帳號密碼，以及 Expiration（過期時間）和 Max-All-Session（最大連線時間）。
    - 注意 MySQL 需要開啟遠端連線的權限。
3. 用帳號密碼生成 QR code
    - 使用 Python 的 qrcode module 將帳號密碼轉為 QR code。
4. 在網頁上顯示 QR code
    - 使用 Python 的 http.server module 處理 HTTP request。
    - 在網頁上顯示 QR code 和帳號密碼。
    - 需要讓 browser 自動更新頁面。
5. 刪除過期帳號
    - 在 radcheck 找已經過期的帳號，刪除 radcheck, radusergroup, radacct 中該帳號的資訊。

## 結果呈現
- 登入頁面:
![](https://i.imgur.com/S0IbWT3.png)
- 影片連結:
[https://www.youtube.com/watch?v=0rrjOjy6sxg](https://www.youtube.com/watch?v=0rrjOjy6sxg)




## 實驗總結
- 難題與解決方法：
    - Free Radius 3 與 Ubuntu 20.04 有版本上的問題，於是我們採用 Free Radius 2 搭配 Ubuntu 16.04
    - Remote query SQL 在確認訪客是否登入以及是否 expired 較為困難，我們利用定時訪問來確定當下的訪客帳密還能使用。
    - NFC 以及 QR code 在確認是否有此裝置時遇到 asynchronize 的問題，我們使用 js的 Promise 來確定執行順序。

- 心得：
	這次 final project ，我們這組利用 lab2 所學到的 Free Radius 系統，配上 QR code 以及 NFC 加快登入網路，其中遇到的困難都被我們一一解決，在過程中，我們更了解如何設定 Free Radius ，加入 expiration 系統，並學會如何遠端操作 SQL，也接觸比較冷門的 web NFC 裝置，學會如何在網站上使用 NFC scanning。


## 分工(貢獻均等，依學號排序)
- B08201047 許祖源 
	- QRcode Server
	- 設計登出機制
- B08902011 杜展廷 
	- QR code 掃描
	- NFC 感應
	- 裝置偵測
- B08902019 康家豪
	- 網頁美術編排
	- 解析技術文件
	- JavaScript 除錯
- B08902021 蔡仲恩 
	- QRcode Server
	- SQL 對接API
- B08902068 黃政穎
	- Free Radius Server
	- AP 設定
	- Server 對接API
- B08902131 陳泊源
	- 網頁美術編排
	- NFC 感應
	- JavaScript 除錯

<!-- 
### 登入方式
* 內部人員：使用證件（如學生證）透過 NFC 掃描登入 FreeRADIUS。
* 外部人員：掃描 QR code 登入網路，且外部人員帳號為隨機帳號並有流量限制。
![](https://i.imgur.com/VvSlCkV.png)

### 使用的技術
* FreeRADIUS：做身分驗證與帳號登入。
* SQL：作為 FreeRADIUS 後端，可以方便地自動化更改帳號密碼等。
* Crontab：可設定每日執行，用來每日定時更新的訪客帳號。
* HTML & PHP & JS：作為網頁前後端。
* NFC：掃描學生證卡號，作為快速輸入帳密的技術。
* QR code：以掃描的方式快速輸入訪客帳密。
## 實驗中遇到的難題與解決方法
- 

## 實驗心得總結

- 我們原本打算使用最新的 FreeRadius 3 ，但是他跟我們使用的作業系統版本不合，所以我們返回使用 FreeRadius 2 ，並使用 Ubuntu 16.04.7 來當作我們的作業系統。
- FreeRadius 要額外設定跟 expiration 相關的的模組來實作帳號過期的功能。
- 因為我們 QR code 的登入是給訪客使用，所以我們使用另一台電腦去生成訪客帳號密碼並 remote insert SQL。在這方面最大的挑戰是如何去知道訪客帳號是否 expired 或是是否已經登入。
- NFC 屬於實驗性質的API，因此實作資料較少。
- QR code 在索取鏡頭權限的部分會遇到非同步的問題。
 -->

<!-- 
## 影片內容
1. Device偵測
    畫面中有手機和電腦的登入頁面(是否具nfc)，看出裝置不同登入頁面的差別，(關閉筆電相機功能，只能輸入)
1. 登入
    1. 拍攝每個按鈕都可以點兩下(代表有返回的效果)
	1. NFC:拍攝手機掃學生證，接著聚焦到手機登入成功頁面
	1. 手動:(?)看著qrcode上的文字輸入，並登入成功
	1. QRCode:拍攝手機掃電腦的畫面，兩者皆入鏡
1. QRCode 自動刷新(承2.4)
    拍攝手機掃電腦的畫面，兩者皆入鏡，掃完後同時拍攝登入成功以及qrcode切換，(?)可以再用另一台手機掃，代表兩個qrcode皆可成功登入
1. 計時服務(承3)
    登入成功後，過個(?)秒被切斷的畫面
    

## Demo大綱
1. 專題介紹 (1 min) - 蔡
1. 專題動機 (1 min) - ~~優秀的~~祖源
1. 網路架構 (1 min) - 黃
1. 實驗詳細步驟 (3 min) - 黃蔡杜 
1. 結果呈現 (5 min) - 康
1. 實驗總結 (1 min) - 陳
1. 分工 (1 min) - 杜
1. QA (2 min) - 即興發揮
 -->