[![Build Status](https://travis-ci.org/PetrSladek/obrok.svg?branch=master)](https://travis-ci.org/PetrSladek/obrok)

# Obrok-IS
Registrační systém skautského festivalu Obrok 2015, Obrok 2017 a Obrok 19

# Setup
Pro první rozjetí je potřeba:
- naimportovat do MySQL databázi ze souboru `database.sql`
- nastavit správné údaje v konfiguraci v souboru `app/config/config.local.neon`
- spustit server (např. pomocí `run.bat`)
- pustit aplikaci v prohlížeči a zaregistrovat se jako servis tým
- nastavit si v databázi nejvyšší práva pro svou osobu `UPDATE person SET role = 'database serviceteam-edit groups-edit' WHERE person.id = 1;`
- nasintalovat less css `npm install -g less` a povolit si v PHPStormu filewatcher
