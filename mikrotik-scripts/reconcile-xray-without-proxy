:local token "<TELEGRAM_BOT_TOKEN>"
:local chat "<TELEGRAM_GROUP_ID>"
:local thread "<TELEGRAM_THREAD_ID>"

:local idMain [/tool netwatch find where name="watch-xray-main"]
:local idB1   [/tool netwatch find where name="watch-xray-backup1"]
:local idB2   [/tool netwatch find where name="watch-xray-backup2"]
:local idB3   [/tool netwatch find where name="watch-xray-backup3"]

:local hostMain ""
:local hostB1 ""
:local hostB2 ""
:local hostB3 ""

:if ([:len $idMain] > 0) do={
    :set hostMain [/tool netwatch get $idMain comment]
    :if ($hostMain = "") do={ :set hostMain [/tool netwatch get $idMain host] }
}
:if ([:len $idB1] > 0) do={
    :set hostB1 [/tool netwatch get $idB1 comment]
    :if ($hostB1 = "") do={ :set hostB1 [/tool netwatch get $idB1 host] }
}
:if ([:len $idB2] > 0) do={
    :set hostB2 [/tool netwatch get $idB2 comment]
    :if ($hostB2 = "") do={ :set hostB2 [/tool netwatch get $idB2 host] }
}
:if ([:len $idB3] > 0) do={
    :set hostB3 [/tool netwatch get $idB3 comment]
    :if ($hostB3 = "") do={ :set hostB3 [/tool netwatch get $idB3 host] }
}

:local mainLocal [/tool netwatch print count-only where name="watch-local-main" status="up"]
:local b1Local   [/tool netwatch print count-only where name="watch-local-backup1" status="up"]
:local b2Local   [/tool netwatch print count-only where name="watch-local-backup2" status="up"]
:local b3Local   [/tool netwatch print count-only where name="watch-local-backup3" status="up"]

:local mainRemote [/tool netwatch print count-only where name="watch-xray-main" status="up"]
:local b1Remote   [/tool netwatch print count-only where name="watch-xray-backup1" status="up"]
:local b2Remote   [/tool netwatch print count-only where name="watch-xray-backup2" status="up"]
:local b3Remote   [/tool netwatch print count-only where name="watch-xray-backup3" status="up"]

:local stMain "DOWN"
:local stB1 "DOWN"
:local stB2 "DOWN"
:local stB3 "DOWN"

:if (($mainLocal > 0) and ($mainRemote > 0)) do={ :set stMain "UP" }
:if (($mainLocal = 0) and ($mainRemote > 0)) do={ :set stMain "LOCAL_DOWN" }
:if (($mainLocal > 0) and ($mainRemote = 0)) do={ :set stMain "REMOTE_DOWN" }

:if (($b1Local > 0) and ($b1Remote > 0)) do={ :set stB1 "UP" }
:if (($b1Local = 0) and ($b1Remote > 0)) do={ :set stB1 "LOCAL_DOWN" }
:if (($b1Local > 0) and ($b1Remote = 0)) do={ :set stB1 "REMOTE_DOWN" }

:if (($b2Local > 0) and ($b2Remote > 0)) do={ :set stB2 "UP" }
:if (($b2Local = 0) and ($b2Remote > 0)) do={ :set stB2 "LOCAL_DOWN" }
:if (($b2Local > 0) and ($b2Remote = 0)) do={ :set stB2 "REMOTE_DOWN" }

:if (($b3Local > 0) and ($b3Remote > 0)) do={ :set stB3 "UP" }
:if (($b3Local = 0) and ($b3Remote > 0)) do={ :set stB3 "LOCAL_DOWN" }
:if (($b3Local > 0) and ($b3Remote = 0)) do={ :set stB3 "REMOTE_DOWN" }

:local target "none"

:if (($mainLocal > 0) and ($mainRemote > 0)) do={
    :set target "main"
} else={
    :if (($b1Local > 0) and ($b1Remote > 0)) do={
        :set target "backup1"
    } else={
        :if (($b2Local > 0) and ($b2Remote > 0)) do={
            :set target "backup2"
        } else={
            :if (($b3Local > 0) and ($b3Remote > 0)) do={
                :set target "backup3"
            }
        }
    }
}

:local current "none"

:if ([/ip route print count-only where routing-table="r_to_vpn" dst-address="0.0.0.0/0" gateway="172.18.20.6" active] > 0) do={ :set current "main" }
:if ([/ip route print count-only where routing-table="r_to_vpn" dst-address="0.0.0.0/0" gateway="172.18.21.6" active] > 0) do={ :set current "backup1" }
:if ([/ip route print count-only where routing-table="r_to_vpn" dst-address="0.0.0.0/0" gateway="172.18.22.6" active] > 0) do={ :set current "backup2" }
:if ([/ip route print count-only where routing-table="r_to_vpn" dst-address="0.0.0.0/0" gateway="172.18.23.6" active] > 0) do={ :set current "backup3" }

:log warning ("reconcile-xray: current=" . $current . " target=" . $target)

:local mainRoute [/ip route find where comment="xray-main"]
:local b1Route   [/ip route find where comment="xray-backup1"]
:local b2Route   [/ip route find where comment="xray-backup2"]
:local b3Route   [/ip route find where comment="xray-backup3"]

:local text ""
:local activeLine "ACTIVE: NONE"

:if ($target = "none") do={
    :if ($current != "none") do={
        :set text "ALL_XRAY_DEAD"
    }
} else={
    :if ($target != $current) do={

        :if ($target = "main") do={
            /ip route set $mainRoute disabled=no
            /ip route set $b1Route disabled=yes
            /ip route set $b2Route disabled=yes
            /ip route set $b3Route disabled=yes
            :set text "XRAY_MAIN_RESTORED_SWITCH_BACK_TO_MAIN"
            :set activeLine ("ACTIVE: XRAY - " . $hostMain)
            :log warning "reconcile-xray: switched to main"
        }

        :if ($target = "backup1") do={
            /ip route set $mainRoute disabled=yes
            /ip route set $b1Route disabled=no
            /ip route set $b2Route disabled=yes
            /ip route set $b3Route disabled=yes
            :set text "XRAY_SWITCH_TO_BACKUP1"
            :set activeLine ("ACTIVE: XRAY1 - " . $hostB1)
            :log warning "reconcile-xray: switched to backup1"
        }

        :if ($target = "backup2") do={
            /ip route set $mainRoute disabled=yes
            /ip route set $b1Route disabled=yes
            /ip route set $b2Route disabled=no
            /ip route set $b3Route disabled=yes
            :set text "XRAY_SWITCH_TO_BACKUP2"
            :set activeLine ("ACTIVE: XRAY2 - " . $hostB2)
            :log warning "reconcile-xray: switched to backup2"
        }

        :if ($target = "backup3") do={
            /ip route set $mainRoute disabled=yes
            /ip route set $b1Route disabled=yes
            /ip route set $b2Route disabled=yes
            /ip route set $b3Route disabled=no
            :set text "XRAY_SWITCH_TO_BACKUP3"
            :set activeLine ("ACTIVE: XRAY3 - " . $hostB3)
            :log warning "reconcile-xray: switched to backup3"
        }
    }
}

:if ($text != "") do={
    :if ($target = "none") do={ :set activeLine "ACTIVE: NONE" }

    :local report ($text . "\r\n" . $activeLine . "\r\n" . \
        "XRAY " . $hostMain . " - " . $stMain . "\r\n" . \
        "XRAY1 " . $hostB1 . " - " . $stB1 . "\r\n" . \
        "XRAY2 " . $hostB2 . " - " . $stB2 . "\r\n" . \
        "XRAY3 " . $hostB3 . " - " . $stB3)

    :local url ("https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=" . $chat . "&message_thread_id=" . $thread . "&text=" . $report)

    :do {
        :local r [/tool fetch url=$url http-percent-encoding=yes output=user as-value check-certificate=no]
        :log info ("TG sent direct: " . $text . " status=" . ($r->"status"))
    } on-error={
        :log error ("TG send direct failed: " . $text)
    }
}
