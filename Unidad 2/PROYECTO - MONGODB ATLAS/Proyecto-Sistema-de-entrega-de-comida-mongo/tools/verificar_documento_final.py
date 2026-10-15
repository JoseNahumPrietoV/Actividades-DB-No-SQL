import zipfile
import hashlib
import xml.etree.ElementTree as ET

f1 = 'DOCUMENTO_TECNICO_SNACKVER_UNIDAD2.docx'
f2 = 'documentacion/DOCUMENTO_TECNICO_SNACKVER_UNIDAD2.docx'

h1 = hashlib.sha256(open(f1, 'rb').read()).hexdigest()
h2 = hashlib.sha256(open(f2, 'rb').read()).hexdigest()

print(f"Hash f1: {h1}")
print(f"Hash f2: {h2}")
print(f"Archivos idénticos (SHA-256): {h1 == h2}")

with zipfile.ZipFile(f1, 'r') as z:
    names = z.namelist()
    media = [n for n in names if n.startswith('word/media/')]
    print(f"\nTotal archivos multimedia embebidos: {len(media)}")
    for m in sorted(media):
        print(f"  - {m} ({z.getinfo(m).file_size} bytes)")
    
    doc_xml = z.read('word/document.xml').decode('utf-8')
    footer_xml = z.read('word/footer1.xml').decode('utf-8')
    styles_xml = z.read('word/styles.xml').decode('utf-8')
    
    print("\n--- Verificación de Estructura y Estilos ---")
    print(f"Campo dinámico PAGE en pie de página: {'PAGE' in footer_xml}")
    print(f"Campo dinámico NUMPAGES en pie de página: {'NUMPAGES' in footer_xml}")
    print(f"Fuente Times New Roman en documento: {'Times New Roman' in doc_xml}")
    print(f"Presencia de fuentes ajenas (Arial): {'Arial' in doc_xml}")
    print(f"Presencia de fuentes ajenas (Calibri): {'Calibri' in doc_xml}")
    print(f"Interlineado 1.5 (w:line='360'): {'w:line=\"360\"' in doc_xml or 'w:line=\"360\"' in styles_xml}")
    print(f"Márgenes 1 pulgada (1440 dxa): {'w:top=\"1440\"' in doc_xml and 'w:left=\"1440\"' in doc_xml}")
    print(f"Tamaño Carta (12240 x 15840 dxa): {'w:w=\"12240\"' in doc_xml and 'w:h=\"15840\"' in doc_xml}")
    print(f"Total elementos gráficos <w:drawing>: {doc_xml.count('<w:drawing>')}")
    print(f"Subtema 6.1 presente: {'6.1 Portada Principal' in doc_xml}")
    print(f"Subtema 6.11 presente: {'6.11 Bitácora de Auditoría' in doc_xml}")
    print(f"Subtema 6.12 resumen presente: {'6.12 Módulos Complementarios' in doc_xml}")
    print(f"Pie de figura 6.1 presente: {'Figura 6.1' in doc_xml}")
    print(f"Pie de figura 6.11 presente: {'Figura 6.11' in doc_xml}")
