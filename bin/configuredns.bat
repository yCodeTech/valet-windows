@echo off

wmic nicconfig where (IPEnabled=TRUE) call SetDNSServerSearchOrder ("127.0.0.1", "8.8.8.8")
