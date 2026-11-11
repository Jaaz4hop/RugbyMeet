//Inscription: affichage dynamique entre supporter et gérant de bar (nom, adresse, code postal, ville) + vérifications de base

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('signupForm');
    if (!form) return;

    const barFields = document.getElementById('barFields');
    const cityField = document.getElementById('cityField');
    const barInputs = barFields.querySelectorAll('input');
    const error = document.getElementById('formError');
    //Gestion de l'affichage à l'inscription entre gérant et supporter
    function updateRole() {
        const isGerant = form.elements.role.value === 'gerant';
        //Suppression du champ d'inscription du bar si supporter
        barFields.hidden = !isGerant;
        //Suppression du champ ville pour un gérant (il renseigne la ville de son bar, pas la sienne en tant qu'utilisateur)
        cityField.hidden = isGerant;
        //Ici ci le role est gérant on met en required les infos concernant le bar pour éviter d'avoir un problème au niveau du formulaire si on mettait les champs en required en dur dans le html.
        barInputs.forEach(i => { i.required = isGerant; });
    }
    //Ici on appelle la fonction updateRole à chaques fois que l'utilisateur clique sur le role et également au premier affichage
    form.querySelectorAll('input[name="role"]').forEach(r => r.addEventListener('change', updateRole));
    updateRole();
    // Vérification de base sur les champs à remplir ou si les mots de passes ne correspondent pas, sera plus poussé en php.
    form.addEventListener('submit', e => {
        error.hidden = true;
        let msg = '';
        if (!form.checkValidity()) msg = 'Merci de remplir tous les champs obligatoires correctement.';
        else if (form.password.value !== form.password_confirm.value) msg = 'Les mots de passe ne correspondent pas.';
        if (msg) {
            e.preventDefault();
            error.textContent = msg;
            error.hidden = false;
        }
    });
});
