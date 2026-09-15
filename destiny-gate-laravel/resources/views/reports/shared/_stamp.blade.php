{{-- CSS-only circular stamp (dompdf has unreliable SVG textPath support, so this avoids SVG). --}}
<div style="text-align:center; margin: 22px 0 4px;">
    <div style="position:relative; display:inline-block; width:104px; height:104px; border-radius:50%; border:2.5px solid #1a6b3c; transform:rotate(-8deg); opacity:0.82;">
        <div style="position:absolute; left:6px; top:6px; right:6px; bottom:6px; border-radius:50%; border:1px dashed #1a6b3c; opacity:0.6;"></div>
        <div style="position:absolute; left:14px; top:14px; right:14px; bottom:14px; border-radius:50%; border:1.5px solid #1a6b3c; display:table;">
            <div style="display:table-cell; vertical-align:middle; text-align:center;">
                <div style="font-size:6.5px; font-weight:bold; color:#1a6b3c; letter-spacing:1px; line-height:1.3;">DESTINYGATE<br>INSTITUTE</div>
                <div style="font-size:11.5px; font-weight:bold; color:#1a6b3c; margin-top:4px; border-top:0.5px solid #1a6b3c; border-bottom:0.5px solid #1a6b3c; padding:2px 0; display:inline-block;">{{ $stampLabel ?? 'OFFICIAL' }}</div>
                <div style="font-size:6.5px; font-weight:bold; color:#1a6b3c; margin-top:4px;">* VERIFIED *</div>
            </div>
        </div>
    </div>
</div>
