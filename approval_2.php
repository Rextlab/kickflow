<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
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

            $comment = $data['data']['ticket']['inputs'][27]['value'];
            $anti_social_check_results = $data['data']['ticket']['inputs'][28]['value'];
            $employee = $data['user']['employeeId'];
            $user_id = $data['data']['ticket']['author']['email'];


            if ($user_id == 'labo@rext.work' && $event_type == 'ticket_completed') {
                $approve_result = approve($comment);
                var_dump($approve_result);
            }
            if ($user_id == 'labo@rext.work' && $event_type == 'ticket_denied') {
                $deny_result = deny($comment);
                var_dump($deny_result);
            }
            if ($event_type == 'ticket_opened') {
                $selected_company = $data['data']['ticket']['inputs'][3]['value'];
                $application_type = $data['data']['ticket']['inputs'][4]['value'];

                $approve_conditions = [
                    '新規取引先の本社と取引開始' => empty($selected_company),
                    '新規取引先の営業店と取引開始' => empty($selected_company),
                    '既存取引先の新規営業店と取引開始' => !empty($selected_company),
                    '既存取引先の会社自体の情報の修正・更新' => !empty($selected_company),
                    '既存取引先の営業所情報の修正・更新' => !empty($selected_company),
                ];

                $return_conditions = [
                    '新規取引先の本社と取引開始' => !empty($selected_company),
                    '新規取引先の営業店と取引開始' => !empty($selected_company),
                    '既存取引先の新規営業店と取引開始' => empty($selected_company),
                    '既存取引先の会社自体の情報の修正・更新' => empty($selected_company),
                    '既存取引先の営業所情報の修正・更新' => empty($selected_company),
                ];

                $return_comments = [
                    '新規取引先の本社と取引開始' => '新規取引先の場合は「取引先選択」を空欄にしてください。
                                                既存取引先の場合は「申請種別」を再度ご確認ください。',
                    '新規取引先の営業店と取引開始' => '新規取引先の場合は「取引先選択」を空欄にしてください。
                                                既存取引先の場合は「申請種別」を再度ご確認ください。',
                    '既存取引先の新規営業店と取引開始' => '既存取引先の場合は「取引先選択」で取引先を選択してください。
                                                新規取引先の場合は「申請種別」を再度ご確認ください。',
                    '既存取引先の会社自体の情報の修正・更新' => '既存取引先の場合は「取引先選択」で取引先を選択してください。
                                                新規取引先の場合は「申請種別」を再度ご確認ください。',
                    '既存取引先の営業所情報の修正・更新' => '既存取引先の場合は「取引先選択」で取引先を選択してください。
                                                新規取引先の場合は「申請種別」を再度ご確認ください。',
                ];

                if (isset($approve_conditions[$application_type]) && $approve_conditions[$application_type]) {
                    $approve_result = approve($wf_id);
                    echo '承認しました';
                } elseif (isset($return_conditions[$application_type]) && $return_conditions[$application_type]) {
                    $return_result = returnTicket($wf_id);
                    $comment = $return_comments[$application_type];
                    $comment_result = addComment($wf_id, $comment);
                    echo $comment;
                }
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
