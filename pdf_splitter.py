#!/usr/bin/env python3
"""
PDF Splitter for Constitutional Document Processing
Splits large PDF files into smaller chunks to prevent memory issues during analysis.
"""

import os
import sys
from pathlib import Path

try:
    import PyPDF2
except ImportError:
    print("PyPDF2 not found. Installing...")
    os.system("pip install PyPDF2")
    import PyPDF2

def split_pdf(input_path, output_dir, max_pages_per_chunk=50):
    """
    Split a PDF file into smaller chunks.
    
    Args:
        input_path (str): Path to the input PDF file
        output_dir (str): Directory to save the split PDF chunks
        max_pages_per_chunk (int): Maximum pages per chunk (default: 50)
    
    Returns:
        list: List of paths to the created chunk files
    """
    
    # Ensure output directory exists
    Path(output_dir).mkdir(parents=True, exist_ok=True)
    
    chunk_files = []
    
    with open(input_path, 'rb') as input_file:
        pdf_reader = PyPDF2.PdfReader(input_file)
        total_pages = len(pdf_reader.pages)
        
        print(f"Processing PDF with {total_pages} pages...")
        print(f"Creating chunks with maximum {max_pages_per_chunk} pages each...")
        
        chunk_num = 1
        start_page = 0
        
        while start_page < total_pages:
            end_page = min(start_page + max_pages_per_chunk, total_pages)
            
            # Create chunk filename
            input_filename = Path(input_path).stem
            chunk_filename = f"{input_filename}_chunk_{chunk_num:02d}_pages_{start_page+1}-{end_page}.pdf"
            chunk_path = Path(output_dir) / chunk_filename
            
            # Create PDF writer for this chunk
            pdf_writer = PyPDF2.PdfWriter()
            
            # Add pages to this chunk
            for page_num in range(start_page, end_page):
                pdf_writer.add_page(pdf_reader.pages[page_num])
            
            # Write the chunk
            with open(chunk_path, 'wb') as output_file:
                pdf_writer.write(output_file)
            
            chunk_files.append(str(chunk_path))
            print(f"Created chunk {chunk_num}: {chunk_filename} (pages {start_page+1}-{end_page})")
            
            # Move to next chunk
            start_page = end_page
            chunk_num += 1
    
    print(f"\nSuccessfully created {len(chunk_files)} chunks in {output_dir}")
    return chunk_files

def main():
    """Main function to split the Constitutional PDF."""
    
    # Define paths
    input_pdf = r"C:\Users\stayn\OneDrive\Desktop\2023\lawexaAPIV2\lawexa-api-v2\StatuteFile\CONSTITUTION-OF-THE-FEDERAL-REPUBLIC-OF-NIGERIA-1999-CAP.pdf"
    output_directory = r"C:\Users\stayn\OneDrive\Desktop\2023\lawexaAPIV2\lawexa-api-v2\StatuteFile\chunks"
    
    # Check if input file exists
    if not os.path.exists(input_pdf):
        print(f"Error: Input PDF not found at {input_pdf}")
        sys.exit(1)
    
    print("Starting PDF splitting process...")
    print(f"Input: {input_pdf}")
    print(f"Output directory: {output_directory}")
    print("-" * 60)
    
    try:
        chunk_files = split_pdf(input_pdf, output_directory, max_pages_per_chunk=50)
        
        print("\n" + "="*60)
        print("PDF SPLITTING COMPLETED SUCCESSFULLY")
        print("="*60)
        print(f"Total chunks created: {len(chunk_files)}")
        print("\nChunk files:")
        for i, chunk_file in enumerate(chunk_files, 1):
            print(f"  {i}. {Path(chunk_file).name}")
        
        print(f"\nAll chunks saved to: {output_directory}")
        print("\nYou can now safely analyze these smaller PDF chunks without memory issues.")
        
    except Exception as e:
        print(f"Error during PDF splitting: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    main()