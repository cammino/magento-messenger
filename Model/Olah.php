<?php class Cammino_Messenger_Model_Olah extends Mage_Core_Model_Abstract
{
    public function __construct() {
        $this->canal_id = Mage::getStoreConfig('messenger/olah_config/olah_canal_id');
        $this->token = Mage::getStoreConfig('messenger/olah_config/olah_token');
        $this->curl = curl_init();
    }

    /**
     * Exemplo da documentação:
        Dados Técnico:
        URL: https://api.olah.app/ws/mensagem
        MÉTODO: POST
        AUTENTICAÇÃO: Bearer Token
        TOKEN: eyJhbGciOiJIUzI1NiIsIdR5cCI6IkpXVCJ9.eyJzdWIiOiJmYWJpb0BuZXRqYWNhcmVpLmNvbS5iciIsImp0aSI 6IjE2MGVmYmNjLTRiOGUtNGExYi1iMjA4LWU1MDA5MmZlZDU0YyIsImlhdCI6MTU0ODUzMjcxNywicm9sI joiYXBpX2FjY2VzcyIstmlkIjoiNWM0Y2JiZWNkZDUyNmI1YTg2NjdkNjc4IiwibmJmIjoxNTQ4NTMyNzE3LC JleHAiOjE1NTg5MDA3MTcsImlzcyI6ImNvbXphZGEuY29tLmJyIiwiYXVkIjoiQ29temFkYUF1ZGllbmNpZVN seiJ9.taE9QuXZ-0PDuNfTxMaqd8MoPV1xZA8IRV6cplZYcz1
        Propriedades:
         • •
        • • • •
        JSON: {
        "Texto": "Imagem",
        "Contato": "5517997668514",
        "Imagem": "https://viagenscinematograficas.com.br/wp-content/uploads/2018/06/Monte-Verde-Dicas-O- que-Fazer-7-740x431.jpg",
        "CanalId": "5c4cc335dd526b5a8667df39" }
        * @param string $message Message to be sent
        * @param string $number Phone number wich will be sent to
     */
    public function sendMessage($message, $number) {
        $number = str_replace('-', '', preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '', $number)));
        if (count($number) <= 11) {
            $number = '55' . (string)$number;
        }
        $body = array(
            "Texto" => $message,
            "Contato" => $number,
            "CanalId" => $this->canal_id
        );
        Mage::log($body, null, 'cammino_messenger.log');
        $authorization = "Authorization: Bearer " . $this->token;        
        curl_setopt_array($this->curl, array(
            CURLOPT_URL => 'https://apiv3.ihelpchat.com/api/v2/customers/send-message?sendName=false',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $authorization )
        ));
        $response = curl_exec($this->curl);
        $err = curl_error($this->curl);
        curl_close($this->curl);
        if ($err) {
            Mage::log($err, null, 'cammino_messenger.log');
        } else {
            Mage::log($response, null, 'cammino_messenger.log');
        }
    }
}