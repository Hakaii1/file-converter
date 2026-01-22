import sys
from rembg import remove
from PIL import Image

def process(input_path, output_path):
    try:
        inp = Image.open(input_path)
        output = remove(inp)
        output.save(output_path)
        print("Success")
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python rembg_worker.py input output")
        sys.exit(1)
    
    process(sys.argv[1], sys.argv[2])
