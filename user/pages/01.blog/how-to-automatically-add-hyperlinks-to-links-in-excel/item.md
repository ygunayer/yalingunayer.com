---
title: How to Automatically Add Hyperlinks to Link Texts in Excel
date: 02:43 09/19/2010 
taxonomy:
    category: blog
    tag: [visual basic, excel, office]
---

I was making a list of movies I own in Excel 2010, and I was also adding links to their pages in IMDB. However, while Excel does automatically convert typed links into hyperlinks, it doesn’t convert pasted ones.

I could do that manually (copy the link text, right click on the cell, select Hyperlink, paste the link into the address bar on the dialog window and click OK) but that would be too time-consuming, considering I had over 250 entries. So I needed something automated to do that for me.

The most basic of these is a function called ```HYPERLINK()```. It turns the text in a different cell into a hyperlink and pastes it into the cell it’s typed in. It also lets you “hide” the link behind a text you specify, but in any case I would have to use another cell for each cell containing the link text. See the syntax:

```
HYPERLINK(link_location; friendly_name)
```

And here’s the description for the parameters:

> **Link_location** is the text giving the path and file name to the document to be opened, a hard drive location, UNC address, or URL path.  
> **Friendly_name** is text or a number that is displayed in the cell. If omitted, the cell displays the Link_location text.  

I don’t want to waste one cell for each links, so I need a “smarter”, more customizable and more automated way. And this is exactly what macros provide.

If you are using Excel 2010 or Excel 2007, there’s a high chance that macro buttons aren’t shown on your task bar (or “ribbon”). Those buttons are moved into a tab called ```Developer``` in Excel 2007 and 2010, and that tab is hidden by default. I’ll explain how to show it in Excel 2010, but I don’t have a copy of Office 2007 or Excel 2007 itself, so I can’t do that for Excel 2007. Here’s a link from Microsoft’s website that shows how: [http://office.microsoft.com/en-ca/excel-help/show-the-developer-tab-or-run-in-developer-mode-HA010173052.aspx](http://office.microsoft.com/en-ca/excel-help/show-the-developer-tab-or-run-in-developer-mode-HA010173052.aspx)

This is how Excel 2010 ribbon looks like when the developer tab is hidden:

To show the developer tab, click the “File” button and select “Options”.

![File Options Menu](images/file_options.jpg)

The “Excel Options” window should be open now. Switch to “Customize Ribbon” tab on the left menu.

![Enable Developer Tab](images/excel_options.jpg)

The list on the right is where you select which tabs you want to hide or show in the main window. As you can see, the “Developer” checkbox is unchecked. Click on that checkbox to check it.

![Enable Developer Tab](images/developer_checked.jpg)

Click on the “OK” button at the bottom, and the developer tab should be visible.

![Enable Developer Tab](images/ribbon_devtab.jpg)

Now, to actually write the macro we want. Switch to developer tab and hit the “Visual Basic” button. You can also use the ```Alt+F11``` hotkey.

![Visual Basic Editor Button](images/vba.jpg)

A window titled “Microsoft Visual Basic for Application” will open up. There, click on the “Insert” button on the top and select “Module”.

![Insert Module Menu](images/vba_insmod.jpg)

A new module will be created and opened for editing. This is where we’ll be coding our subroutine (macro). When we use the ```Ctrl+S``` hotkey, the subroutine will be saved and we’ll be able to use that subroutine as a macro back in Excel.

![Empty Workbook](images/vba_newmod.jpg)

As the window title suggests, Excel macros are actually Visual Basic scripts. The syntax and keywords of VB are as simple as they get. “Sub” means “define subroutine”, “Dim” means “define variable or reference”, “Set” means “set the reference to”, “MsgBox” means “create a message box” and so on.

A simple subroutine that defines a variable called mystr, sets it value to “Hello, world!” and displays it in a message box could be written like this:

```
Sub MySubroutine()
    Dim mystr As String
    mystr = "Hello, world!"
    MsgBox mystr
End Sub
```

Cell contents are also very easy to access. If you’d like to show the “A1″ cell’s contents;

```
Sub MySubroutine()
    MsgBox Range("A1").Value
End Sub
```

Excel macros also allow us to access the selected cell or range on the active worksheet. So let’s write a subroutine to show the contents of the selected cell in a message box. Here’s the code:

```vb
Sub MySubroutine()
    MsgBox Selection.Value
End Sub
```

After writing this code, save the current VBA module by pressing ```Ctrl+S```, and switch back to Excel window. If you get a dialog with Yes/No options, asking if you’d like to save the workbook as a macro enabled one, hit ```Yes``` for now. I’ll explain what that means later on.

When back at Excel again, select a single cell (```A1``` for example), switch over to developer tab and hit the macro button, or simply press ```Alt+F8```. A window with a list of all available macros should appear:

![Macro List](images/macrolist.jpg)

Here on this window, you can either assign a hotkey (using the “Options” button on the rigth) for your macros or run them directly. Excel closes the window automatically when you click on the “Run” button, so there’s no point in adding a hotkey right now.

When you run the macro, a message box containing the value of the selected cell will pop up:

![Message Box Sample](images/msgbox.jpg)

“Range” and “Selection” actually return a set or an array of objects. For example, calling ```Range(“A1:C3″)``` would return all cells between A1 and C3, and Selection would return all selected cells. Knowing this, we can now write a macro that loops through our selection and tries and converts link texts into hyperlinks.

But here’s a catch. In order Excel to add a hyperlink to a text, the specified URL or file name must be in the correct format. So we’ll have to verify all cell values before creating hyperlinks from them.

Now, verifying a URL or a file name by traditional string operations is a very, VERY hard work. So instead, we’ll be using regular expressions. Microsoft also added built-in regular expression classes in their libraries, but they aren’t referenced in new modules by default. We’ll have to manually reference them in our module. To do that, switch back to VBA window, clean your subroutine code, click on “Tools” at the top menu and select “References”.

![References Window](images/refs.jpg)

The References window might take a while to load at first launch, so be patient. Once it loads, find ```Microsoft VBScript Regular Expressions 5.5``` on the list.

![RegEx Dependency](images/refregex.jpg)

Check the check box next to it, press the OK button and you can now use regular expression classes or methods in your code.

![RegEx Dependency](images/refregex2.jpg)

Let’s go back to our code. I’ll explain everything step-by-step.

This is our first line.

```
Sub MySubroutine()
```

Create a ```RegExp``` object called “regex” with this regular expression pattern:

```
“((https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\))+[wd:#@%/;$()~_?+-=.&]*)”
```

(Note: This pattern is taken from [http://www.geekzilla.co.uk/view2D3B0109-C1B2-4B4E-BFFD-E8088CBC85FD.htm](http://www.geekzilla.co.uk/view2D3B0109-C1B2-4B4E-BFFD-E8088CBC85FD.htm)

```
    Dim regex As RegExp
    Set regex = New RegExp
    regex.Pattern = "((https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\))+[wd:#@%/;$()~_?+-=.&]*)"
```

For each cells in our selection…

```
For Each cell In Selection
```

If the current cell’s value is a well-formatted file name or a URL (test the regex pattern), then…

```
        If (regex.Test(cell.Value) = True) Then
```

Add a hyperlink object which references the current cell, links to its value and displays its value on itself, on the current (active) worksheet…

```
            ActiveSheet.Hyperlinks.Add Anchor:=cell, Address:=cell.Value, TextToDisplay:=cell.Value
```

Close the if, for and subroutine scopes.

```
        End If
    Next
End Sub
```

That’s all about it. For simplicity reasons, here’s the entire code:

```
Sub MySubroutine()
    Dim regex As RegExp
    Set regex = New RegExp
    regex.Pattern = "((https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\))+[wd:#@%/;$()~_?+-=.&]*)"
    For Each cell In Selection
        If (regex.Test(cell.Value) = True) Then
            ActiveSheet.Hyperlinks.Add Anchor:=cell, Address:=cell.Value, TextToDisplay:=cell.Value
        End If
    Next
End Sub
```

Now, about that Yes/No/Cancel dialog box I told you about. I’m not exactly sure about Excel 2007, but in Excel 2010, macros are disabled by default on a workbook (file) basis (*.xlsx files). You can either enable macros on a non-macro file for a session, or save the file as a macro-enabled workbook file (*.xlsm files). That dialog box asks you if you’d like to enable macros for that session (“Yes” option) or if you’d like to save the file as a new XLSM file (“No” option). Either way is fine, but the macro we wrote might be handy in other situations too, so I’ll save a new XLSM file (you’ll end up losing the macro when you close the file if you chose “Yes”).

![Macro-Enabled File Save Dialog](images/yesnodialog.jpg)

When you click on “No”, a file save dialog will pop up. There, choose “Excel Macro-Enabled Workbook (*.xlsm)” in the “Save as Type:” box and type in a file name to your liking. I’ll keep the same name as my macro-free workbook file.

![Macro-Enabled File Save Dialog](images/savedialog.jpg)

We can now close the VBA window (titled “Microsoft Visual Basic for Applications”) and switch back to Excel (note the changed filename). There, select the link texts you want to add hyperlinks to (don’t be afraid to make mistakes, we’ve already taken precautions, see “regex”)…

![Rows are Selected](images/selection.jpg)

Open up the “Macros” window (Alt+F8), select “MySubroutine” (or whatever you named your macro) and click on the “Run” button.

![Macros Window](images/runmacro.jpg)

And, voila! All well-formatted URLs in our selection are converted into hyperlinks! Even if we had well formed file addresses (with “file://” in the beginning) in our selection, they would be converted to hyperlinks.

![Voila!](images/fileconvert.jpg)

URLs or file names outside of our selection won’t be converted.

![Non-URL Text are Ignored](images/selection2.jpg)

That’s all for now. Before I finish, however, I want to add that I’d like to see that as a built-in function on a newer Excel version. As this article suggests, it’s not a very hard thing to do anyway.