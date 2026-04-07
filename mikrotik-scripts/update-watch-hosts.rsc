:local key "\"address\": \""

:local cfg ""
:local txt ""
:local p
:local e
:local hostOrIp ""
:local ip ""

# MAIN
:set cfg "xray-configs/config.json"

:if ([:len [/file find where name=$cfg]] > 0) do={
    :set txt [/file get $cfg contents]
    :set p [:find $txt $key]

    :if ($p != nil) do={
        :set p ($p + [:len $key])
        :set e [:find $txt "\"" $p]

        :if ($e != nil) do={
            :set hostOrIp [:pick $txt $p $e]

            :do {
                :set ip [:resolve $hostOrIp]
                /tool netwatch set [find where name="watch-xray-main"] host=$ip comment=$hostOrIp
                :log info ("MAIN -> " . $hostOrIp . " (" . $ip . ")")
            } on-error={
                :log error ("MAIN resolve failed: " . $hostOrIp)
            }
        }
    }
}

# BACKUP1
:set cfg "xray-configs2/config.json"

:if ([:len [/file find where name=$cfg]] > 0) do={
    :set txt [/file get $cfg contents]
    :set p [:find $txt $key]

    :if ($p != nil) do={
        :set p ($p + [:len $key])
        :set e [:find $txt "\"" $p]

        :if ($e != nil) do={
            :set hostOrIp [:pick $txt $p $e]

            :do {
                :set ip [:resolve $hostOrIp]
                /tool netwatch set [find where name="watch-xray-backup1"] host=$ip comment=$hostOrIp
                :log info ("BACKUP1 -> " . $hostOrIp . " (" . $ip . ")")
            } on-error={
                :log error ("BACKUP1 resolve failed: " . $hostOrIp)
            }
        }
    }
}

# BACKUP2
:set cfg "xray-configs3/config.json"

:if ([:len [/file find where name=$cfg]] > 0) do={
    :set txt [/file get $cfg contents]
    :set p [:find $txt $key]

    :if ($p != nil) do={
        :set p ($p + [:len $key])
        :set e [:find $txt "\"" $p]

        :if ($e != nil) do={
            :set hostOrIp [:pick $txt $p $e]

            :do {
                :set ip [:resolve $hostOrIp]
                /tool netwatch set [find where name="watch-xray-backup2"] host=$ip comment=$hostOrIp
                :log info ("BACKUP2 -> " . $hostOrIp . " (" . $ip . ")")
            } on-error={
                :log error ("BACKUP2 resolve failed: " . $hostOrIp)
            }
        }
    }
}

# BACKUP3
:set cfg "xray-configs4/config.json"

:if ([:len [/file find where name=$cfg]] > 0) do={
    :set txt [/file get $cfg contents]
    :set p [:find $txt $key]

    :if ($p != nil) do={
        :set p ($p + [:len $key])
        :set e [:find $txt "\"" $p]

        :if ($e != nil) do={
            :set hostOrIp [:pick $txt $p $e]

            :do {
                :set ip [:resolve $hostOrIp]
                /tool netwatch set [find where name="watch-xray-backup3"] host=$ip comment=$hostOrIp
                :log info ("BACKUP3 -> " . $hostOrIp . " (" . $ip . ")")
            } on-error={
                :log error ("BACKUP3 resolve failed: " . $hostOrIp)
            }
        }
    }
}
