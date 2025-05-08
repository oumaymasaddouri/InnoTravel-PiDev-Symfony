$files = Get-ChildItem -Path "templates" -Recurse -Filter "*.html.twig" | Select-String -Pattern "extends 'basebackoffice.html.twig'" | Select-Object Path -Unique

foreach ($file in $files) {
    $content = Get-Content -Path $file.Path -Raw
    
    # Check if the file has {% block body %} but not {% block content %}
    if ($content -match "{% block body %}") {
        Write-Host "Processing $($file.Path)"
        
        # Replace {% block body %} with {% block content %}
        $newContent = $content -replace "{% block body %}", "{% block content %}"
        
        # Replace {% endblock body %} with {% endblock content %} if it exists
        $newContent = $newContent -replace "{% endblock body %}", "{% endblock content %}"
        
        # Replace {% endblock %} that follows {% block body %} with {% endblock content %}
        $newContent = $newContent -replace "{% block body %}(.*?){% endblock %}", "{% block content %}`$1{% endblock content %}"
        
        # Write the updated content back to the file
        Set-Content -Path $file.Path -Value $newContent
        
        Write-Host "Updated $($file.Path)"
    }
}

Write-Host "All files have been processed."
