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

            // $comment = $data['data']['ticket']['inputs'][27]['value'];
            // $anti_social_check_results = $data['data']['ticket']['inputs'][28]['value'];
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

            if ($event_type == 'tiket_approved') {
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
