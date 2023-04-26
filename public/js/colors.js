function colorway(color) {
    switch (true) {
        case (color == 1):
            document.getElementById('cssChange').innerHTML =
            `:root {--fg: #0d5291; --me-arm: #0d5291; --me-body: #1e90ff; --ac: #9ACD32;}
            @media (prefers-color-scheme: dark) {:root {--fg: #f5f5f5;}}`;
            document.getElementById('animate-stop1').setAttribute('values', '#9ACD32; #556B2F; 008B8B; #9ACD32');
            document.getElementById('animate-stop2').setAttribute('values', '#556B2F; 008B8B; #9ACD32; #556B2F');
            break;
        case (color == 2):
            document.getElementById('cssChange').innerHTML =
            `:root {--fg: #8B0000; --me-arm: #8B0000; --me-body: #B22222; --ac: #FF8C00;}
            @media (prefers-color-scheme: dark) {:root {--fg: #f5f5f5;}}`;
            document.getElementById('animate-stop1').setAttribute('values', '#FF8C00; #FFA07A; #FF4500; #FF8C00;');
            document.getElementById('animate-stop2').setAttribute('values', '#FFA07A; #FF4500; #FF8C00; #FFA07A;');
            break;
        case (color == 3):
            document.getElementById('cssChange').innerHTML =
            `:root {--fg: #4B0082; --me-arm: #4B0082; --me-body: #9932CC; --ac: #FFC0CB;}
            @media (prefers-color-scheme: dark) {:root {--fg: #f5f5f5;}}`;
            document.getElementById('animate-stop1').setAttribute('values', '#FFC0CB; #DB7093; #FF69B4; #FFC0CB;');
            document.getElementById('animate-stop2').setAttribute('values', '#DB7093; #FF69B4; #FFC0CB; ##DB7093');
            break;
        case (color == 4):
            document.getElementById('cssChange').innerHTML =
            `:root {--fg: #753619; --me-arm: #753619; --me-body: #8B4513; --ac: #7a8787;}
            @media (prefers-color-scheme: dark) {:root {--fg: #f5f5f5;}}`;
            document.getElementById('animate-stop1').setAttribute('values', '#7a8787; #696969; #2F4F4F; #7a8787;');
            document.getElementById('animate-stop2').setAttribute('values', '#696969; #2F4F4F; #7a8787; #696969;');
            break;
        case (color == 5):
            document.getElementById('cssChange').innerHTML =
            `:root {--fg: #323031; --me-arm: #323031; --me-body: #3D3B3C; --ac: #7F7979;}
            @media (prefers-color-scheme: dark) {:root {--fg: #f5f5f5;}}`;
            document.getElementById('animate-stop1').setAttribute('values', '#7F7979; #696969; #778899; #7F7979;');
            document.getElementById('animate-stop2').setAttribute('values', '#696969; #778899; #7F7979; #696969;');
            break;
    }
}