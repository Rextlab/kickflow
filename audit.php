<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
set_time_limit(0); // 実行時間を無制限にする
try {
    // Kickflow API token
    define('ACCESS_TOKEN', '0464b68ee28e42d77bdbb063782589d4'); // Kickflowのアクセストークン


    // チケットを差し戻す
    function returnTicket($wf_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.kickflow.com/v1/tickets/' . $wf_id . '/reject',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'to' => 0,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ACCESS_TOKEN,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            echo "Error: HTTP $httpCode\n";
            echo "Response: $response\n";
            return false;
        }
    }

    // コメントを追加する
    function addComment($wf_id, $comment)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.kickflow.com/v1/tickets/' . $wf_id . '/comments',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'body' => $comment,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ACCESS_TOKEN,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            echo "Error: HTTP $httpCode\n";
            echo "Response: $response\n";
            return false;
        }
    }

    // チケットを棄却する
    function deny($wf_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.kickflow.com/v1/tickets/' . $wf_id . '/deny',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ACCESS_TOKEN,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            echo "Error: HTTP $httpCode\n";
            echo "Response: $response\n";
            return false;
        }
    }

    // チケットを承認する
    function approve($wf_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.kickflow.com/v1/tickets/' . $wf_id . '/approve',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ACCESS_TOKEN,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            echo "Error: HTTP $httpCode\n";
            echo "Response: $response\n";
            return false;
        }
    }

    //完了していないチケットを全て取得する
    function getTickets($n)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.kickflow.com/v1/tickets?status[]=in_progress&perPage=100&page=' . $n,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ACCESS_TOKEN,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            echo "Error: HTTP $httpCode\n";
            echo "Response: $response\n";
            return false;
        }
    }


    // Kickflow Webhookがリクエストを送信した場合の処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Kickflow Webhookから送られたJSONデータを取得
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        // 必要なデータが送信されているかチェック
        if (isset($data['user'])) {

            //イベントタイプを取得
            $event_type = $data['eventType'];

            // ワークフローIDを取得
            $wf_id = $data['data']['ticket']['id'];
            $employee = $data['user']['employeeId'];
            $user_id = $data['data']['ticket']['author']['email'];
            $approve_result = approve($wf_id);
            if ($approve_result) {
                echo '承認しました';
            }

            // HTTP 200 OKを返す
            http_response_code(200);
            exit;
        } else {
            // 必要なデータがない場合、HTTP 400 Bad Requestを返す
            http_response_code(400);
            exit;
        }
    } else {
        // POST以外のリクエストに対してHTTP 405 Method Not Allowedを返す
        // http_response_code(405);
        // exit;
        //チケットを全て取得し全てのチケットを承認する
        $cnt = 0;
        for ($i = 1; $i < 11; $i++) {
            $tickets = getTickets($i);
            //チケットを表示する（デバック用）
            // echo '<pre>';
            // var_dump($tickets);
            // echo '</pre>';
            // exit;
            if (empty($tickets)) {
                break;
            }
            foreach ($tickets as $ticket) {
                $wf_id = $ticket['id'];
                $approve_result = approve($wf_id);
                if ($approve_result) {
                    echo $wf_id . 'を承認しました<br>';
                } else {
                    echo $wf_id . 'を承認できませんでした<br>';
                }
                $cnt++;
                if ($cnt % 25 == 0) {
                    sleep(90);
                }
            }
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage(), 3, "kickflow_webhook.log");
    die("Error: " . $e->getMessage());
}
