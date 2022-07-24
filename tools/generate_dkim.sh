#!/usr/bin/env bash

[ "" = "$1" ] && echo "Generates DKIM files for use with wp-email-essentials." && echo "Usage: $0 domain.tld dkim_selector passphrase" && echo "DNS record will be dkim_selector._domainkey.domain.tld" && exit 1

R_SELECTOR=$( echo $RANDOM | md5sum | head -c 16 );
R_PASSPHRASE=$( echo $RANDOM | md5sum | head -c 32 )$( echo $RANDOM | md5sum | head -c 32 );

DOMAIN_TLD=$1
SELECTOR=${2:-$R_SELECTOR}
PASSPHRASE=${3:-$R_PASSPHRASE}

[ ! -d $DOMAIN_TLD.wpes ] && mkdir $DOMAIN_TLD.wpes
cd $DOMAIN_TLD.wpes

echo "Generating private key for $DOMAIN_TLD [$DOMAIN_TLD.key]"
openssl genrsa -aes256 -passout pass:$PASSPHRASE -out $DOMAIN_TLD.key 2048
echo "Generating public key for $DOMAIN_TLD [$DOMAIN_TLD.crt]"
openssl rsa -in $DOMAIN_TLD.key -pubout -passin pass:$PASSPHRASE > $DOMAIN_TLD.crt
echo "Writing password to file [$DOMAIN_TLD.pass]"
echo "$PASSPHRASE" > $DOMAIN_TLD.pass
echo "Writing selector to file [$DOMAIN_TLD.selector]"
echo "$SELECTOR" > $DOMAIN_TLD.selector
KEYCONTENT=$(cat $DOMAIN_TLD.crt | grep -v 'PUBLIC KEY' | tr --delete '\n')
echo "Generating DNS record for $SELECTOR._domainkey.$DOMAIN_TLD. [$DOMAIN_TLD.dns-record.txt]"
echo $SELECTOR'._domainkey.'$DOMAIN_TLD'. IN TXT "v=DKIM1; k=rsa; p='$KEYCONTENT'"' > $DOMAIN_TLD.dns-record.txt
