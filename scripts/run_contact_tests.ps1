$ErrorActionPreference = 'Stop'
$day2 = (Get-Date).AddDays(4).ToString('yyyy-MM-dd')
function PostTo($body, $out) {
    $resp = Invoke-WebRequest -Uri 'http://localhost:8000/contact/contact.php' -Method Post -Body $body -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop
    $resp.Content | Out-File -FilePath $out -Encoding UTF8
}

# Test 1: Contact/Devis (sans_vehicule=1) -> review expected
$body1 = @{ form_action='review'; contact_action=''; customer_type='individual'; email='test@example.com'; nom='Dupont'; prenom='Jean'; adresse='1 Rue'; code_postal='74950'; ville='Scionzier'; telephone='0612345678'; sans_vehicule='1'; sujet='Devis test'; message='Demande de devis pour remplacement freins' }
PostTo $body1 't1.html'

# Test 2: Contact/Devis short message -> expect missing-field error
$body2 = @{ form_action='review'; contact_action=''; customer_type='individual'; email='test2@example.com'; nom='Dupont'; prenom='Jean'; adresse='1 Rue'; code_postal='74950'; ville='Scionzier'; telephone='0612345678'; sans_vehicule='1'; sujet='Devis test'; message='Hi' }
PostTo $body2 't2.html'

# Test 3: vehicle_visit valid -> review expected
$body3 = @{ form_action='review'; contact_action='vehicle_visit'; customer_type='individual'; email='test3@example.com'; nom='Martin'; prenom='Paul'; adresse='10 Rue'; code_postal='74950'; ville='Scionzier'; telephone='0612345678'; immatriculation='AA-123-BB'; date_essai=$day2; sujet='Essai'; message='Je souhaite essayer le vehicule' }
PostTo $body3 't3.html'

# Test 4: vehicle_visit missing immatriculation -> expect missing-field error
$body4 = @{ form_action='review'; contact_action='vehicle_visit'; customer_type='individual'; email='test4@example.com'; nom='Martin'; prenom='Paul'; adresse='10 Rue'; code_postal='74950'; ville='Scionzier'; telephone='0612345678'; date_essai=$day2; sujet='Essai'; message='Je souhaite essayer le vehicule' }
PostTo $body4 't4.html'

# Test 5: vehicle_visit submit -> final submit, expect Merci modal text
$body5 = @{ form_action='submit'; contact_action='vehicle_visit'; customer_type='individual'; email='test5@example.com'; nom='Martin'; prenom='Paul'; adresse='10 Rue'; code_postal='74950'; ville='Scionzier'; telephone='0612345678'; immatriculation='AA-123-BB'; date_essai=$day2; sujet='Essai'; message='Je souhaite essayer le vehicule' }
PostTo $body5 't5.html'

# Summarize
$results = [ordered]@{}
$results['t1_has_review'] = (Select-String -Path t1.html -Pattern 'Recapitulatif de votre demande' -SimpleMatch) -ne $null
$results['t2_has_error'] = (Select-String -Path t2.html -Pattern 'Pour demander un devis' -SimpleMatch) -ne $null
$results['t3_has_review'] = (Select-String -Path t3.html -Pattern 'Recapitulatif de votre demande' -SimpleMatch) -ne $null
$results['t4_has_error'] = (Select-String -Path t4.html -Pattern 'Veuillez remplir tous les champs obligatoires' -SimpleMatch) -ne $null
$results['t5_has_merci'] = (Select-String -Path t5.html -Pattern 'Merci' -SimpleMatch) -ne $null
Write-Output (ConvertTo-Json $results)
