#!/usr/bin/env python3
"""Test: intentar leer DWG con ezdxf y con ODA."""
import subprocess, os, sys, time

DWG_FILE = r'C:\Temp\oda_in\test.dwg'
ODA_EXE  = r'C:\Program Files\ODA\ODAFileConverter 26.4.0\ODAFileConverter.exe'

print("=== TEST 1: ezdxf leer DWG directo ===")
try:
    import ezdxf
    doc, auditor = ezdxf.recover.readfile(DWG_FILE)
    print("ezdxf OK — entidades:", len(list(doc.modelspace())))
except Exception as e:
    print("ezdxf falló:", e)

print()
print("=== TEST 2: ODA DXF ACAD2013 ===")
out2 = r'C:\Temp\oda_acad2013'
os.makedirs(out2, exist_ok=True)
r = subprocess.run([ODA_EXE, r'C:\Temp\oda_in', out2, 'ACAD2013', 'DXF', '0', '0'],
                   capture_output=True, timeout=30)
time.sleep(2)
files2 = {f: os.path.getsize(os.path.join(out2, f)) for f in os.listdir(out2)}
print("exit:", r.returncode, "| files:", files2)
print("stdout:", r.stdout[:300])
print("stderr:", r.stderr[:300])

print()
print("=== TEST 3: ODA DXF ACAD2018 ===")
out3 = r'C:\Temp\oda_acad2018'
os.makedirs(out3, exist_ok=True)
r = subprocess.run([ODA_EXE, r'C:\Temp\oda_in', out3, 'ACAD2018', 'DXF', '0', '0'],
                   capture_output=True, timeout=30)
time.sleep(2)
files3 = {f: os.path.getsize(os.path.join(out3, f)) for f in os.listdir(out3)}
print("exit:", r.returncode, "| files:", files3)

print()
print("=== TEST 4: ODA DXF ACAD2010 ===")
out4 = r'C:\Temp\oda_acad2010'
os.makedirs(out4, exist_ok=True)
r = subprocess.run([ODA_EXE, r'C:\Temp\oda_in', out4, 'ACAD2010', 'DXF', '0', '0'],
                   capture_output=True, timeout=30)
time.sleep(2)
files4 = {f: os.path.getsize(os.path.join(out4, f)) for f in os.listdir(out4)}
print("exit:", r.returncode, "| files:", files4)
