let mtgLabel = `<DieCutLabel Version="8.0" Units="twips">
                            <PaperOrientation>Landscape</PaperOrientation>
                            <Id>Small30336</Id>
                            <PaperName>30336 1 in x 2-1/8 in</PaperName>
                            <DrawCommands>
                                <RoundRectangle X="0" Y="0" Width="1440" Height="3060" Rx="180" Ry="180" />
                            </DrawCommands>
                            <ObjectInfo>
                                <TextObject>
                                    <Name>ADDRESS</Name>
                                    <ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
                                    <BackColor Alpha="0" Red="255" Green="255" Blue="255" />
                                    <LinkedObjectName></LinkedObjectName>
                                    <Rotation>Rotation0</Rotation>
                                    <IsMirrored>False</IsMirrored>
                                    <IsVariable>False</IsVariable>
                                    <HorizontalAlignment>Center</HorizontalAlignment>
                                    <VerticalAlignment>Middle</VerticalAlignment>
                                    <TextFitMode>AlwaysFit</TextFitMode>
                                    <UseFullFontHeight>True</UseFullFontHeight>
                                    <Verticalized>False</Verticalized>
                                    <StyledText>
                                        <Element>
                                            <String></String>
                                            <Attributes>
                                                <Font Family="Arial" Size="16" Bold="True" Italic="False" Underline="False" Strikeout="False" />
                                                <ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
                                            </Attributes>
                                        </Element>
                                    </StyledText>
                                </TextObject>
                                <Bounds X="130" Y="57" Width="2846" Height="581" />
                            </ObjectInfo>
                            <ObjectInfo>
                                <AddressObject>
                                    <Name>NAME</Name>
                                    <ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
                                    <BackColor Alpha="0" Red="255" Green="255" Blue="255" />
                                    <LinkedObjectName></LinkedObjectName>
                                    <Rotation>Rotation0</Rotation>
                                    <IsMirrored>False</IsMirrored>
                                    <IsVariable>True</IsVariable>
                                    <HorizontalAlignment>Center</HorizontalAlignment>
                                    <VerticalAlignment>Top</VerticalAlignment>
                                    <TextFitMode>ShrinkToFit</TextFitMode>
                                    <UseFullFontHeight>True</UseFullFontHeight>
                                    <Verticalized>False</Verticalized>
                                    <StyledText>
                                        <Element>
                                            <String>test</String>
                                            <Attributes>
                                                <Font Family="Arial" Size="12" Bold="False" Italic="False" Underline="False" Strikeout="False" />
                                                <ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
                                            </Attributes>
                                        </Element>
                                    </StyledText>
                                    <ShowBarcodeFor9DigitZipOnly>False</ShowBarcodeFor9DigitZipOnly>
                                    <BarcodePosition>AboveAddress</BarcodePosition>
                                    <LineFonts>
                                        <Font Family="Arial" Size="12" Bold="False" Italic="False" Underline="False" Strikeout="False" />
                                    </LineFonts>
                                </AddressObject>
                                <Bounds X="130" Y="690" Width="2846" Height="195" />
                            </ObjectInfo>
                            <ObjectInfo>
                                <BarcodeObject>
                                    <Name>BARCODE</Name>
                                    <ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
                                    <BackColor Alpha="0" Red="255" Green="255" Blue="255" />
                                    <LinkedObjectName></LinkedObjectName>
                                    <Rotation>Rotation0</Rotation>
                                    <IsMirrored>False</IsMirrored>
                                    <IsVariable>True</IsVariable>
                                    <Text>12345</Text>
                                    <Type>Code39</Type>
                                    <Size>Medium</Size>
                                    <TextPosition>Bottom</TextPosition>
                                    <TextFont Family="Arial" Size="8" Bold="False" Italic="False" Underline="False" Strikeout="False" />
                                    <CheckSumFont Family="Arial" Size="8" Bold="False" Italic="False" Underline="False" Strikeout="False" />
                                    <TextEmbedding>None</TextEmbedding>
                                    <ECLevel>0</ECLevel>
                                    <HorizontalAlignment>Center</HorizontalAlignment>
                                    <QuietZonesPadding Left="0" Top="0" Right="0" Bottom="0" />
                                </BarcodeObject>
                                <Bounds X="130" Y="923" Width="2846" Height="435" />
                            </ObjectInfo>
                        </DieCutLabel>`;

let address = "PriceBustersGames.com\n4771 Bayou Blvd. #6\nPensacola, FL 32503\n(850)912-8922";

function printMTGLabel(text, id, qty){
  let label = dymo.label.framework.openLabelXml(mtgLabel);
  label.setObjectText("ADDRESS", address);
  label.setObjectText("NAME", text);
  label.setObjectText("BARCODE", id);

  let printers = dymo.label.framework.getPrinters();
  if(printers.length === 0){
    throw "No DYMO printers are installed.";
  }

  let printerName;
  for(let i = 0, n = printers.length; i < n; i++){
    let printer = printers[i];
    if(printer.printerType == "LabelWriterPrinter"){
      printerName = printer.name;
      break;
    }
  }

  let printParams = {
    'copies': qty
  };
  label.print(printerName, dymo.label.framework.createLabelWriterPrintParamsXml(printParams));
}
