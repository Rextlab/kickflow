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

            //コメントの作成
            $comment = '反社チェックで重大な懸念が指摘されています。
                もし事実であった場合には取引できませんので、このまま取り下げください。
                同姓同名の別人・別法人である場合、その証明として以下を添付して回付ください。
                ・取引相手の本名、生年月日が記載された公的な証明書（免許証・保険証・パスポート等）、またはそれに準ずるもの';

            // 反社チェックのステップの位置を取得
            $antsocial_team_number = 0;
            for ($i = 0; $i < count($data['data']['ticket']['steps']); $i++) {
                if ($data['data']['ticket']['steps'][$i]['title'] == '反社チェック') {
                    $antsocial_team_number = $i;
                    break;
                }
            }
            //反社チェックの結果を取得
            $antsocial_cheeck = '';
            for ($i = 0; $i < count($data['data']['ticket']['inputs']); $i++) {
                if ($data['data']['ticket']['inputs'][$i]['formFieldId'] == 'ccae1753-747e-4806-86fd-ec9edb89a001') {
                    $antsocial_cheeck = $data['data']['ticket']['inputs'][$i]['value'];
                    break;
                }
            }
            //反社チェックが承認、その次のステップが未承認かつ反社チェックの結果が「重大懸念あり」の場合コメントを追加する
            if (
                isset($data['data']['ticket']['steps'][$antsocial_team_number]['status']) &&
                $data['data']['ticket']['steps'][$antsocial_team_number]['status'] === 'completed' &&

                isset($data['data']['ticket']['steps'][$antsocial_team_number + 1]['status']) &&
                $data['data']['ticket']['steps'][$antsocial_team_number + 1]['status'] === 'in_progress' &&

                $antsocial_cheeck === '重大懸念あり'
            ) {
                // コメントを追加する
                addComment($wf_id, $comment);
                //結果を表示する
                echo "Comment added successfully.\n";
            } else {
                // 反社チェックが承認、その次のステップが未承認かつ反社チェックの結果が「重大懸念あり」でない場合、何もしない
                echo "No action needed.\n";
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
        http_response_code(405);
        exit;
    }
} catch (Exception $e) {
    error_log($e->getMessage(), 3, "kickflow_webhook.log");
    die("Error: " . $e->getMessage());
}
