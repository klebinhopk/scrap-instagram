<?php
  //include('formatArray.php');

  function getSeguidores($id, $end_cursor = ""){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.instagram.com/graphql/query/?query_hash=c76146de99bb02f6415203be841dd25a&variables={"id":"'.$id.'","include_reel":true,"fetch_mutual":false,"first":10,"after":"'.$end_cursor.'"}');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = 'Sec-Fetch-Mode: cors';
    $headers[] = 'Cookie: *"';
    $headers[] = 'X-Ig-App-Id: *';
    $headers[] = 'Accept-Encoding: gzip, deflate, br';
    $headers[] = 'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7';
    $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.87 Safari/537.36';
    $headers[] = 'Accept: */*';
    $headers[] = 'Referer: https://www.instagram.com';
    $headers[] = 'Authority: www.instagram.com';
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    $headers[] = 'Sec-Fetch-Site: same-origin';
    $headers[] = 'X-Csrftoken: *';
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
    for ($i=0; $i <= 1; $i++) { 
      if($i > 0) {
        $resultado = getSeguidores('26669533',$end_cursor); //ID DA CONTA QUE QUER SEGUIR OS USUARIOS
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
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.instagram.com/web/friendships/'.$id.'/follow/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    
    $headers = array();
    $headers[] = 'Sec-Fetch-Mode: cors';
    $headers[] = 'Origin: https://www.instagram.com';
    $headers[] = 'Accept-Encoding: gzip, deflate, br';
    $headers[] = 'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7';
    $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.87 Safari/537.36';
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    $headers[] = 'Cookie: *';
    $headers[] = 'X-Csrftoken: *';
    $headers[] = 'X-Ig-App-Id: *';
    $headers[] = 'X-Instagram-Ajax: *';
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $headers[] = 'Accept: */*';
    $headers[] = 'Referer: https://www.instagram.com/somenekcomunicacao/followers/?hl=pt-br';
    $headers[] = 'Authority: www.instagram.com';
    $headers[] = 'Sec-Fetch-Site: same-origin';
    $headers[] = 'Content-Length: 0';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    echo $result.'<br/>';
    
    curl_close($ch);
  }

  $ids = listaId();

  foreach ($ids as $id) {
    echo follow($id);
    sleep(10);
  }