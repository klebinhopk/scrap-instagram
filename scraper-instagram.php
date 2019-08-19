<?php
  //include('formatArray.php');

  define('USERNAME', ""); //LOGIN
  define('PASSWORD', ""); //SENHA

  define('USERAGENT', "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36");
  define('COOKIE', USERNAME.".txt");
  
  //OBS: É RECOMENDADO ALTERAR SOMENTE O ID SE VOCÊ NÃO TEM CONHECIMENTOS
  define('ID', "26669533"); //ID DA CONTA ALVO QUE VOCE QUER SEGUIR
  define('PG', "49"); //TOTAL DE PAGINAS QUE O SCRAPPER VAI RODAR 49 = 50
  define('SEGUIDORES', "10"); //QUANTIDADE DE SEGUIDORES POR PAGINA
  define('TIME', "10"); //TEMPO PARA SEGUIR

  function login_inst() {
    @unlink(dirname(__FILE__)."/!instagram/".COOKIE);

    $url="https://www.instagram.com/accounts/login/?force_classic_login";

    $ch  = curl_init(); 

    $arrSetHeaders = array(
        "User-Agent: USERAGENT",
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: deflate, br',
        'Connection: keep-alive',
        'cache-control: max-age=0',
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, $arrSetHeaders);         
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__)."/!instagram/".COOKIE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__)."/!instagram/".COOKIE);
    curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $page = curl_exec($ch);
    curl_close($ch);  

    //var_dump($page);

    // try to find the actual login form
    if (!preg_match('/<form method="POST" id="login-form" class="adjacent".*?<\/form>/is', $page, $form)) {
        die('Failed to find log in form!');
    }

    $form = $form[0];

    // find the action of the login form
    if (!preg_match('/action="([^"]+)"/i', $form, $action)) {
        die('Failed to find login form url');
    }

    $url2 = $action[1]; // this is our new post url
    // find all hidden fields which we need to send with our login, this includes security tokens
    $count = preg_match_all('/<input type="hidden"\s*name="([^"]*)"\s*value="([^"]*)"/i', $form, $hiddenFields);

    $postFields = array();

    // turn the hidden fields into an array
    for ($i = 0; $i < $count; ++$i) {
        $postFields[$hiddenFields[1][$i]] = $hiddenFields[2][$i];
    }

    // add our login values
    $postFields['username'] = USERNAME;
    $postFields['password'] = PASSWORD;   

    $post = '';

    // convert to string, this won't work as an array, form will not accept multipart/form-data, only application/x-www-form-urlencoded
    foreach($postFields as $key => $value) {
        $post .= $key . '=' . urlencode($value) . '&';
    }

    $post = substr($post, 0, -1);   

    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $page, $matches);

    $cookieFileContent = '';

    foreach($matches[1] as $item) 
    {
        $cookieFileContent .= "$item; ";
    }

    $cookieFileContent = rtrim($cookieFileContent, '; ');
    $cookieFileContent = str_replace('sessionid=""; ', '', $cookieFileContent);

    $arrSetHeaders = array(
        'origin: https://www.instagram.com',
        'authority: www.instagram.com',
        'upgrade-insecure-requests: 1',
        'Host: www.instagram.com',
        "User-Agent: USERAGENT",
        'content-type: application/x-www-form-urlencoded',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: deflate, br',
        "Referer: $url",
        "Cookie: $cookieFileContent",
        'Connection: keep-alive',
        'cache-control: max-age=0',
    );

    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $arrSetHeaders);     
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    sleep(5);
    $page = curl_exec($ch);
    
    preg_match_all('/Set\-Cookie\: sessionid\=(.*?)\;/s', $page, $sessionid);
    preg_match_all('/Set\-Cookie\: csrftoken\=(.*?)\;/s', $page, $csrftoken);
    preg_match_all('/Set\-Cookie\: sessionid\=(.*?)\%/s', $page, $idUser);

    $session = [];
    $session['sessionid'] = $sessionid[1][0];
    $session['csrftoken'] = $csrftoken[1][0];
    $session['idUser'] = $idUser[1][0];

    //dd($session);
    return $session;

    curl_close($ch);  
  }

  function getSeguidores($id, $end_cursor = ""){
    $inf = login_inst();
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.instagram.com/graphql/query/?query_hash=c76146de99bb02f6415203be841dd25a&variables={"id":"'.$id.'","include_reel":true,"fetch_mutual":false,"first":'.SEGUIDORES.',"after":"'.$end_cursor.'"}');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = 'Sec-Fetch-Mode: cors';
    $headers[] = 'Cookie: ig_cb=1; rur=VLL; mid=XVmN_AAEAAElkt7JJ-x27SQ-FU7E; csrftoken='.$inf['csrftoken'].'; shbid=18949; shbts=1566150146.7649705; ds_user_id='.$inf['idUser'].'; sessionid='.$inf['sessionid'].'; urlgen=\"{\"50.7.93.84\": 174}:1hzPDT:xuvurS-UefzRZzby1U47hKEC9Ww\"';
    $headers[] = 'X-Ig-App-Id: 936619743392459';
    $headers[] = 'Accept-Encoding: gzip, deflate, br';
    $headers[] = 'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7';
    $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.87 Safari/537.36';
    $headers[] = 'Accept: */*';
    $headers[] = 'Referer: https://www.instagram.com/neymarjr/';
    $headers[] = 'Authority: www.instagram.com';
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    $headers[] = 'Sec-Fetch-Site: same-origin';
    $headers[] = 'X-Csrftoken: '.$inf['csrftoken'].'';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    $array = json_decode($result, true);
    return $array['data']['user']['edge_followed_by'];

    if (curl_errno($ch)) {
      echo $result;
      echo 'Error:' . curl_error($ch);
    }

    curl_close($ch);
  }

  function listaId(){
    $end_cursor = '';
    $lista = [];
    for ($i=0; $i <= PG; $i++) { 
      if($i > 0) {
        $resultado = getSeguidores(ID, $end_cursor); //ID DA CONTA QUE QUER SEGUIR OS USUARIOS
        $end_cursor = $resultado['page_info']['end_cursor'];

        foreach ($resultado['edges'] as $v) {
          $lista[] = $v['node']['id'];
        }

        if($resultado['page_info']['has_next_page'] == 0) {break;}
      }
    }
    return $lista;
  }

  function follow($id){
    $inf = login_inst();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.instagram.com/web/friendships/'.$id.'/follow/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    
    $headers = array();
    $headers[] = 'Sec-Fetch-Mode: cors';
    $headers[] = 'Cookie: ig_cb=1; rur=VLL; mid=XVmN_AAEAAElkt7JJ-x27SQ-FU7E; csrftoken='.$inf['csrftoken'].'; shbid=18949; shbts=1566150146.7649705; ds_user_id='.$inf['idUser'].'; sessionid='.$inf['sessionid'].'; urlgen=\"{\"50.7.93.84\": 174}:1hzPDT:xuvurS-UefzRZzby1U47hKEC9Ww\"';
    $headers[] = 'X-Ig-App-Id: 936619743392459';
    $headers[] = 'Accept-Encoding: gzip, deflate, br';
    $headers[] = 'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7';
    $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.87 Safari/537.36';
    $headers[] = 'Accept: */*';
    $headers[] = 'Referer: https://www.instagram.com/neymarjr/';
    $headers[] = 'Authority: www.instagram.com';
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    $headers[] = 'Sec-Fetch-Site: same-origin';
    $headers[] = 'X-Csrftoken: '.$inf['csrftoken'].'';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    echo $result.'<br/>';
    
    curl_close($ch);
  }

  $ids = listaId();

  foreach ($ids as $id) {
    echo follow($id);
    sleep(TIME);
  }