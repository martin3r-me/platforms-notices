<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $note->name }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body {
            /* DomPDF: bevorzugt Fonts die wirklich auf dem Server verfügbar sind */
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.55;
            color: #111827;
        }
        h1 {
            font-size: 22pt;
            margin: 0 0 10mm 0;
        }
        h2 { font-size: 16pt; margin: 8mm 0 3mm 0; }
        h3 { font-size: 13pt; margin: 6mm 0 2mm 0; }
        p { margin: 0 0 4mm 0; }
        ul, ol { margin: 0 0 4mm 0; padding-left: 6mm; }
        li { margin: 0 0 2mm 0; }
        code {
            font-family: "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 10pt;
            background: #f3f4f6;
            padding: 1px 4px;
            border-radius: 4px;
        }
        pre {
            background: #f3f4f6;
            padding: 10px 12px;
            border-radius: 8px;
            white-space: pre-wrap;
        }
        a { color: #2563eb; text-decoration: underline; }
        hr { border: 0; border-top: 1px solid #e5e7eb; margin: 8mm 0; }
        .meta {
            margin-top: 10mm;
            font-size: 9pt;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>{{ $note->name }}</h1>

    <div>
        {!! \Illuminate\Support\Str::markdown($note->content ?? '') !!}
    </div>

    <div class="meta">
        Erstellt: {{ optional($note->created_at)->format('d.m.Y H:i') }} ·
        Aktualisiert: {{ optional($note->updated_at)->format('d.m.Y H:i') }}
    </div>
</body>
</html>

