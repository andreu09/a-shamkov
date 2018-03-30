<?php
class Bot extends CI_Controller {

    private $confirmationToken  = 'c84eed8c'; // Строка подтверждения 
    private $token  = '9594c9adc40b97fc32d34f3e0cba2eff303803b289f237269970afc8c3adce78a30dfd221d3b7243625a8'; // Ключ доступа сообщества
    private $secretKey = 'TOoo56FFFbbgg'; // Секретный ключ сообщества
    private $code = '666^^^888'; // Код для выполнения операций администрации
    public $versionApiVk = '5.73';

    /*
        Чтение ботом запросов от вк
    */

    public function read()
    {
        if (!isset($_REQUEST)) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'));

        if($data === null) {
            return;
        }

        switch ($data->type) {
            case 'confirmation':
                $this->confirmation($this->code);
                break;    
            case 'message_new':
                $this->message_new($data->object->user_id,$data->object->body);
                break;
            case 'message_reply' :
                header('HTTP/1.1 200 OK');
                echo 'ok';
                break;
            case 'group_join':
                $this->group_join($data->object->user_id);
                break;
            case 'group_leave':
                $this->group_leave($data->object->user_id);
                break;
            default :
                header('HTTP/1.1 200 OK');
                echo 'ok';
        }
    }

    /*
        Сообщение при вступление в группу
    */

    public function group_join($id)
    {
        $userGroupJoin = $this->user_info($id);
        $request_params = array(
            'message'       => $userGroupJoin['generalInformation']['first_name'] .  ','  . ' Спасибо за подписку ❤❤❤',
            'user_id'       => $id,
            'access_token'  => $this->token,
            'read_state'    => 1,
            'v'             => $this->versionApiVk
        );
        $get_params = http_build_query($request_params);
        
        file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
        header('HTTP/1.1 200 OK');
        echo('ok');
    }

    /*
        Сообщение при выходе из группу
    */

    public function group_leave($id)
    {
        $userGroupLeave = $this->user_info($id);
        $request_params = array(
            'message'       => $userGroupLeave['generalInformation']['first_name'] .  ','  . ' Мне жаль, что ты уходишь 😰😰😰',
            'user_id'       => $id,
            'access_token'  => $this->token,
            'read_state'    => 1,
            'v'             => $this->versionApiVk
        );
        $get_params = http_build_query($request_params);
        
        file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
        header('HTTP/1.1 200 OK');
        echo('ok');
    }

    /*
        Подтверждение сервера для ВКонтакте
    */

    public function confirmation($code = '')
    {
        if($code === $this->code){
            echo $this->confirmationToken;

        } else {
            return;
        }
    }

    /*
        Отправка нового сообщения пользователю
        @param $fromId От кого сообщение
        @param $messageBody Текст сообщения
    */

    public function message_new($fromId,$messageBody)
    {
        $user_info_message = $this->user_info($fromId);

        switch($messageBody) {
            case 'Статистика' :
                $message = $user_info_message['generalInformation']['first_name'] . ',' . 
                ' держи свою статистику:<br> ' . 
                '<br> 1) В общем '. $user_info_message['statistics']['totalLikesPhotosProfile'] .  ' ❤ в твоем профиле!' .
                '<br> 2) В разработке...' ;
                break;
            default:
            $message = 'Такой команды я не знаю )= <br><br>' .
                        '1. Узнать статистику команда `Статистика` ';
            break;
        }

        $request_params = array(
            'message'       => $message,
            'user_id'       => $user_info_message['generalInformation']['id'],
            'access_token'  => $this->token,
            'read_state'    => 1,
            'v'             => $this->versionApiVk
        );

        $get_params = http_build_query($request_params);
        
        file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
        header('HTTP/1.1 200 OK');
        echo('ok');

    }

    /*
        Получение информации о пользователе по его id
    */

    public function user_info(int $id)
    {
        // Получает общую информацию о пользователе
        $generalInformation = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$id}&lang=ru&v=5.0"))->response[0];

        // Количество лайков в профиле
        $totalLikesPhotosProfile = 0;
        $userPhotosProfile = json_decode(file_get_contents("https://api.vk.com/method/photos.get?owner_id={$id}&album_id=profile&extended=1&v={$this->versionApiVk}"));
        foreach($userPhotosProfile->response->items as $item) {
            $totalLikesPhotosProfile += $item->likes->count;

        }

        return [
            'statistics'    => [
                'totalLikesPhotosProfile'   => $totalLikesPhotosProfile
            ],
            'generalInformation'    => [
                'first_name'    =>  $generalInformation->first_name,
                'last_name'     =>  $generalInformation->last_name,
                'id'            =>  $generalInformation->id
            ]
        ];
    }
}