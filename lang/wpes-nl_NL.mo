��    ?        Y         p     q     �  �   �  �   �  S   N  t   �          +  T   =     �  �   �  �   \	  �   �	     �
     �
  7   �
          "     *  B   >     �  �   �  �   i     �               &  [   )     �  !   �     �  2   �  $     #   '  t   K     �  O   �     %     3  ;   ;     w     �  �   �  %        8  #   H  <   l     �  
   �  �   �  D  j  �   �  �   ^  �   �  �   �     G     Z  J   c  K   �  �   �  C   �  �   �  �  d  #   T  +   x  �   �  �   �  X     �   �     _     x  s   �       �   #  �   �  �   �  ,   !  "   N  C   q     �     �  &   �  N         Q   �   c   �   ]!     �!     	"     %"     <"     @"  
   �"  /   �"      �"  3   #  '   P#  =   x#  �   �#     8$  Y   R$     �$     �$  O   �$     (%     <%  �   I%  -   �%     &  /   4&  A   d&  "   �&  
   �&  �   �&  ^  |'  �   �(  �   �)  �   =*  �   "+     �+     �+  U   �+  \   M,  �   �,  @   8-  �   y-        "                         
   6   	   :   '       1           ,          2   =      ;       3       5   9         <                 %      !               -                 *           .   8         +      (       0                                  >                            /   #   &   4   7   ?   $   )               Alternative Admins Alternative Admins list saved. But <strong>please do not worry</strong>! <a href="%s" target="_blank">WP-Email-Essentials</a> will set <em class="at-">example-email-at-youtserver-dot-com</em> as sender and set <em>this address</em> as Reply-To header. But <strong>please do not worry</strong>! <a href="%s" target="_blank">WP-Email-Essentials</a> will set <em class="noreply">noreply@%s</em> as sender and set <em>this email address</em> as Reply-To header. Convert CSS to Inline Styles (for Outlook Online, Yahoo Mail, Google Mail, Hotmail) DEFAULTS can be overruled with WordPress filter <code>wpes_defaults</code>, parameters: <code>Array $defaults</code> Default from e-mail Default from name Derive plain-text alternative? (Will derive text-ish body from html body as AltBody) Dump of PHP Mailer object E-mails sent as different domain will probably be marked as spam. Fix the sender-address to always match the sending domain and send original From address as Reply-To: header? Email BODY can be overruled with WordPress filter <code>wpes_body</code>, parameters: <code>String $body_content</code>, <code>PHPMailer $mailer</code> Email HEAD can be overruled with WordPress filter <code>wpes_head</code>, parameters: <code>String $head_content</code>, <code>PHPMailer $mailer</code> Enable sending mail over SMTP? Evaluated path: <code>%s</code> Example Email (actual HTML) - with your filters applied Expanded path: <code>%s</code> Filters Fix sender-address? Found S/MIME identities for the following senders: <code>%s</code> Hostname or -ip If HTML enabled: You can use WordPress filters to augment the HEAD and BODY sections of the HTML e-mail. To add information to the HEAD (or change the title) hook to filter wpes_head. For the body, hook to wpes_body It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s/.smime/</code> to prevent stealing your identity. Mail Key Mail NOT sent to %s Mail sent to %s No No, send with possibly-invalid sender as is. (might cause your mails to be marked as spam!) Password Print debug output of sample mail RegExp matched against subject Rewrite email@addre.ss to email-at-addre-dot-ss@%s Rewrite email@addre.ss to noreply@%s S/MIME Certificate/Private-Key path SETTINGS can be overruled with WordPress filter <code>wpes_settings</code>, parameters: <code>Array $settings</code> Sample email subject Sample mail will be sent to the <a href="%s">Site Administrator</a>; <b>%s</b>. Save settings Secure? Send as HTML? (Will convert non-html body to html-ish body) Send sample mail Send to Services like MandrillApp or SparkPostMail will break S/MIME signing. Please use a different SMTP-service if signing is required. Set folder <code>%s</code> not found. Settings saved. Sign emails with S/MIME certificate Split mail with more than one Recepient into separate mails? Subject-RegExp list saved. Test-email The S/MIME certificate folder is inside the webspace. This is Extremely insecure. Please reconfigure, make sure the folder is outside the website-root %s. The S/MIME certificate folder is writable. This is Extremely insecure. Please reconfigure, make sure the folder is not writable by Apache. If your server is running suPHP, you cannot make the folder read-only for apache. Please contact your hosting provider and ask for a more secure hosting package, one not based on suPHP. The naming convention is: certificate: <code>email@addre.ss.crt</code>, private key: <code>email@addre.ss.key</code>, (optional) passphrase: <code>email@addre.ss.pass</code>. The openssl package for PHP is not installed, incomplete or broken. Please contact your hosting provider. S/MIME signing is NOT available. There is no certificate for the default sender address <code>%s</code>. Start: <a href="https://www.comodo.com/home/email-security/free-email-certificate.php" target="_blank">here</a>. There is no certificate for the default sender address <code>%s</code>. The required certificate is supplied with this plugin. Please copy it to the correct folder. Unmatched subjects Username WP-Email-Essentials is not yet configured. Please fill out the form below. WP-Email-Essentials is not yet configured. Please go <a href="%s">here</a>. You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against ABSPATH (the root of your wordpress). You can configure alternative administrators <a href="%s">here</a>. You can fix this here, or you can let <a href="%s" target="_blank">WP-Email-Essentials</a> fix this automatically upon sending the email. Project-Id-Version: WP Email Essentials
POT-Creation-Date: 2016-09-13 15:13+0200
PO-Revision-Date: 2016-09-13 15:24+0200
Last-Translator: Remon Pel <remon@clearsite.nl>
Language-Team: Clearsite Webdesigners <support@clearsite.nl>
Language: nl_NL
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
X-Generator: Poedit 1.8.7
X-Poedit-Basepath: ..
Plural-Forms: nplurals=2; plural=(n != 1);
X-Poedit-KeywordsList: __;_t;_e;esc_attr__
X-Poedit-SearchPath-0: .
 Alternatieve Administrator-adressen Lijst van alternatieve adressen opgeslagen. Maar <strong>weest niet bevreesd</strong>! <a href="%s" target="_blank">WP-Email-Essentials</a> zal <em class="at-">example-email-at-youtserver-dot-com</em> instellen als afzender en <em>het hier ingestelde adres</em> gebruiken als Reply-To adres. Maar <strong>weest niet bevreesd</strong>! <a href="%s" target="_blank">WP-Email-Essentials</a> zal <em class="noreply">noreply@%s</em> instellen als afzender en <em>het hier ingestelde adres</em> gebruiken als Reply-To adres. Converteer CSS naar Inline Stijl (voor Outlook Online, Yahoo Mail, Google Mail, Hotmail) STANDAARD WAARDEN kunnen worden aangepast middels WordPress filter <code>wpes_default</code>, parameters: <code>Array $defaults</code> Standaard afzender email Standaard afzender naam Genereer normale-tekst-alternatief? (Dit maakt een tekst-achtige variant van de HTML Body voor gebruik als AltBody) Dump van het PHP Mailer object Emails verzonden als vanaf een afwijkend domein hebben grote kans om als spam gemarkeerd te worden. Wil je dat WP-Email-Essentials het adres als Reply-To: header instelt en het adres aanpast? Email BODY kan worden aangepast middels WordPress filter <code>wpes_body</code>, parameters: <code>String $body_content</code>, <code>PHPMailer $mailer</code> Email HEAD kan worden aangepast middels WordPress filter <code>wpes_head</code>, parameters: <code>String $head_content</code>, <code>PHPMailer $mailer</code> Verstuur emails middels een SMTP verbinding? Daadwerkelijk pad: <code>%s</code> Voorbeeld Email (de daadwerkelijke HTML) met jouw filters toegepast Volledig pad: <code>%s</code> Filters Ongeldige afzender adressen aanpassen? S/MIME identiteiten gevonden voor de volgende email-afzenders: <code>%s</code> Servernaam of -ip Indien HTML ingeschakeld: Je kunt de WordPress filters gebruiken om de HEAD en BODY onderdelen van de HTML e-mail te verrijken of aan te passen. Om informatie toe te voegen aan de HEAD, gebruik filter wpes_head. Voor de email BODY, gebruik wpes_body Het is sterk aanbevolen om een map te kiezen <u>buiten</u> je website, bijvoorbeeld <code>%s/.smime/</code> om te voorkomen dat je identiteit wordt gestolen. Email sleutel Mail NIET verzonden naar %s Mail verzonden naar %s Nee Nee, verstuur de email met de mogelijk-ongeldige afzender als zodanig. (hiermee worden de emails mogelijk als spam gemarkeerd!) Wachtwoord Geef debug informatie over verzonden test-email RegExp voor testen van onderwerp Herschrijf email@adr.es naar email-at-adr-dot-es@%s Herschrijf email@adr.es naar noreply@%s Bestandspad naar S/MIME Certificaat en Prive-sleutel-bestaand INSTELLINGEN kunnen worden aangepast middels WordPress filter <code>wpes_settings</code>, parameters <code>Array $settings</code> Voorbeeld email onderwerp Test-email zal worden verzonden naar <a href="%s">de website administrator</a>; <b>%s</b> Instellingen opslaan Beveiligde verbinding? Versturen als HTML? (Dit converteert niet-HTML emails naar HTML-achtige emails) Verstuur test-email Verstuur aan Diensten als MandrillApp of SparkPostMail werken niet goed samen met S/MIME ondertekening. Gebruik een andere SMTP-dienst indien digitale ondertekening vereist is. Ingestelde map <code>%s</code> niet gevonden. Instellingen opgeslagen. Onderteken de emails met een S/MIME certificaat Verstuur emails met meerdere geadresseerden als separatie emails? Onderwerp-RegExp lijst opgeslagen. Test-email Het gekozen S/MIME certificaat pad is gesitueerd binnen de website. Dit is Extreem onveilig. Kies a.u.b. een andere locatie welke niet binnen de website-basis %s ligt. Het gekozen S/MIME certificaat pad is beschrijfbaar. Dit is Extreem onveilig. Kies a.u.b. een andere locatie welke niet schrijfbaar is door Apache. Gebruikt je server suPHP dan is het ONMOGELIJK de folder alleen-lezen te maken voor Apache. Contacteer je hosting leverancier en vraag om een veiliger hosting pakket, welke GEEN gebruik maakt van suPHP. De naamgeving van bestanden is: certificaat: <code>email@adr.es.crt</code>, privé-sleutel-bestand: <code>email@adr.es.key</code>, (optioneel) wachtwoord-bestand: <code>email@adr.es.pass</code>. Het pakket openssl voor PHP is niet geïnstalleerd, is incompleet of stuk. Neem contact op met je hosting aanbieder. S/MIME ondertekening is NIET beschikbaar. Er is geen certificaat voor het standaard afzender adres <code>%s</code>. Begin <a href="https://www.comodo.com/home/email-security/free-email-certificate.php" target="_blank">hier</a> met de aanvraag van een S/MIME certificaat. Er is geen certificaat voor het standaard afzender adres <code>%s</code>. Het vereiste certificaat is meegeleverd met deze plugin, kopieer het a.u.b. naar de juiste map. Niet-afgevangen onderwerpen Gebruikersnaam WP-Email-Essentials is nog niet geconfigureerd. Vul aub onderstaande instellingen in. WP-Email-Essentials is nog niet geconfigureerd, ga a.u.b. naar <a href="%s">deze pagina</a>. Je kunt de map-locatie ook relatief opgeven, bijvoorbeeld ../.smime, welke wordt geëvalueerd t.o.v. ABSPATH, de basis-locatie van WordPress. Je kunt alternatieve ontvangers <a href="%s">hier</a> instellen. Dit kun je hier oplossen, of je kunt <a href="%s" target="_blank">WP-Email-Essentials</a> het automatisch laten oplossen bij het verzenden van de email. 