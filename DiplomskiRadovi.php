<?php
interface iRadovi {
    public function create($url); 
    public function save(); 
    public function read();
}

class DiplomskiRadovi implements iRadovi{
    var $naziv_rada;
    var $tekst_rada;
    var $link_rada;
    var $oib_tvrtke;
      
    function get_html_from_url($url) {
        
        $curl = curl_init();   
        
        //Zaustavi ako se dogodi pogreška
        curl_setopt($curl, CURLOPT_FAILONERROR, 1); 

        //Dozvoli preusmjeravanja
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        
        //Spremi vraćene podatke u varijablu
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        //Postavi timeout
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        
        //Koristi GET metodu
        curl_setopt($curl, CURLOPT_URL, $url);
        
        //Vrati rezultat GET-a u obliku stringa
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        //Izvrši transakciju
        $response = curl_exec($curl);
        //Zatvori spoj
        curl_close($curl);
        return $response;
    }
    
    function create($url) {
        require_once 'simple_html_dom.php';
        
        $response = $this->get_html_from_url($url);
        //Kreiraj parser objekt
        $html = new simple_html_dom();
        
        //Učitaj dobiveni HTML u parser
        $html->load($response);
        
        //Nađi element korištenjem CSS selectora
        $naziv = $html->find('div.fusion-post-content.post-content > h2 > a', 0);
        $link = $naziv->href;
        $naziv = $naziv->plaintext;
        
        $tekst = $html->find('div.fusion-post-content.post-content > div > p', 0);
        $tekst = $tekst->plaintext;
        
        $slika = $html->find('div.fusion-flexslider.flexslider.fusion-post-slideshow > ul.slides > li > div > img', 0);
        
        //Dohvaćanje oib-a
        $oib = basename($slika->src);
        $oib = substr($oib, 0, -4);
        $html->clear();
        
        $this->naziv_rada = $naziv;
        $this->tekst_rada = $tekst;
        $this->link_rada = $link;
        $this->oib_tvrtke = $oib;
        
        echo "<p>Kreiran je objekt DiplomskiRadovi s<br>Nazivom: <b>{$this->naziv_rada}</b>
        <br>Tekstom: <b>{$this->tekst_rada}</b>
        <br>Linkom: <b>{$this->link_rada}</b>
        <br>OIB-om tvrtke: <b>{$this->oib_tvrtke}</b>
        </p>";
    }
    function connect_to_db() {
        // Spajanje na bazu podataka
        $servername = "localhost";
        $database = "u376204397_radovi";
        $username = "u376204397_labosi";
        $password = "Labosi1234!";
        $conn = mysqli_connect($servername, $username, $password, $database);
        if (!$conn) {
            die("Neuspješno spajanje: " . mysqli_connect_error());
        }
        return $conn;
    }

    function read() {
        // Čitanje iz baze pomoću SQL izraza
        $conn = $this->connect_to_db();
        $sql = "select * from diplomski_radovi";
        $result = mysqli_query($conn, $sql);
        $rows = array();
        if (mysqli_num_rows($result) > 0) {
         while($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }}
        $count = count($rows);
        echo "<b>Ukupno $count</b><br>";
        echo json_encode($rows);
    
        mysqli_close($conn);
    } 

    function save() {
        //Spremanje u bazu podataka pomoću SQL izraza
        $conn = $this->connect_to_db();
        $sql = "INSERT INTO diplomski_radovi (naziv_rada, tekst_rada, link_rada, oib_tvrtke) VALUES (?, ?, ?, ?)";

        //
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $this->naziv_rada, $this->tekst_rada, $this->link_rada, $this->oib_tvrtke);
        
        // 
        if ($stmt->execute()) {
            echo "<b>Uspješno spremljeno u bazu podataka</b><br>";
        } else {
            echo "Error: " . $conn->error;
        }
        mysqli_close($conn);
    }
}

$diplomski_rad = new DiplomskiRadovi();
$diplomski_rad->create('https://stup.ferit.hr/index.php/zavrsni-radovi/page/2');
$diplomski_rad->save();
echo "<br><br><b>Učitavanje svih radova iz baze u obliku JSON-a:</b><br>";
$diplomski_rad->read();
?>
